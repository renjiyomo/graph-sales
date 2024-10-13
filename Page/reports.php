<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'a') {
    header("Location: /cartcraft/Register/Login/login.php");
    exit;
}

include 'cartcraft_db.php';

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$userImage = $user['image'];
$userName = $user['names'];

$start_date = $_GET['start-date'] ?? '';
$end_date = $_GET['end-date'] ?? '';
$status = $_GET['status'] ?? 'all';

$query = "SELECT * FROM orders WHERE 1=1";
if ($start_date) {
    $query .= " AND order_date >= '$start_date'";
}
if ($end_date) {
    $query .= " AND order_date <= '$end_date'";
}
if ($status !== 'all') {
    $query .= " AND status = '$status'";
}

$result = mysqli_query($conn, $query);
$sales_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sales_data[] = $row;
}

$totalSales = array_sum(array_column($sales_data, 'total'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports and Analytics</title>
    <link rel="stylesheet" href="css/report.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/chart.js"></script>
</head>

<nav>
    <header class="header">
        <a href="#" class="logo">
            <img class="craft" src="image/craft.png" alt="Logo">
        </a>

        <nav class="navbar">
            <a href="dash.php">Dashboard</a>
            <a href="adminProduct.php">Products</a>
            <a href="usersList.php">User</a>
            <a href="artistsList.php">Artist</a>
            <a href="adminOrders.php">Orders</a>
            <a href="reports.php">Sales</a>

            <div class="profile-dropdown">
                <div class="profile">
                    <img src="image/<?php echo $userImage; ?>" alt="profile_pic" class="profile-pic">
                </div>
                <ul class="dropdown-content">
                    <li>
                        <span class="profile-name"><?php echo htmlspecialchars($userName); ?></span>
                    </li>
                    <li><a href="manageAccount.php">Manage Account</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <script>
        document.querySelector('.profile').addEventListener('click', function(event) {
            event.preventDefault();
            const dropdown = document.querySelector('.dropdown-content');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });

        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.dropdown-content');
            if (!event.target.closest('.profile-dropdown')) {
                dropdown.style.display = 'none';
            }
        });
    </script>
</nav>

<body>
    <main>
        <h1>Sales Reports and Analytics</h1>
        <section id="filters">
            <h2>Filter Reports</h2>
            <form id="filter-form">
                <div class="filter-row">
                    <div class="filter-controls">
                        <label for="start-date">Start Date:</label>
                        <input type="date" id="start-date" name="start-date" value="<?= htmlspecialchars($start_date) ?>">

                        <label class="end-date" for="end-date">End Date:</label>
                        <input type="date" id="end-date" name="end-date" value="<?= htmlspecialchars($end_date) ?>">

                        <label class="order-status" for="status">Order Status:</label>
                        <select id="status" name="status">
                            <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All</option>
                            <option value="d" <?= $status === 'd' ? 'selected' : '' ?>>Delivered</option>
                            <option value="p" <?= $status === 'p' ? 'selected' : '' ?>>Pending</option>
                            <option value="s" <?= $status === 's' ? 'selected' : '' ?>>Shipped</option>
                        </select>
                    </div>
                    <button type="submit">Filter</button>
                </div>
            </form>
        </section>

        <section id="analytics">
            <h2>Sales Overview</h2>
            <div id="total-sales">
                <h4>Total Sales: ₱<?= number_format($totalSales, 2) ?></h4>
            </div>
            <div id="sales-chart">
                <canvas id="salesChart" width="750" height="200"></canvas>
            </div>

            <h2>Recent Sales</h2>
            <table id="sales-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Artist ID</th>
                        <th>Product Name</th>
                        <th>Total Amount</th>
                        <th>Order Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales_data as $sale): ?>
                        <tr>
                            <td><?= htmlspecialchars($sale['order_id']) ?></td>
                            <td><?= htmlspecialchars($sale['artists_id']) ?></td>
                            <td><?= htmlspecialchars($sale['product_name']) ?></td>
                            <td>₱ <?= htmlspecialchars($sale['total']) ?></td>
                            <td><?= htmlspecialchars($sale['order_date']) ?></td>
                            <td><?= htmlspecialchars($sale['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>

    <footer class="section__container footer__container" id="footer">
        <div class="footer__col">
            <h4>Creator</h4>
            <a href="#footer">Arevalo, Kristine Zyra Mae</a>
            <a href="#footer">Bautista, Madel Jandra</a>
            <a href="#footer">Serrano, Mark Erick</a>
        </div>

        <div class="footer__col">
            <h4>Bicol University</h4>
            <a href="#footer">Campus: Polangui</a>
            <a href="#footer">Course: BSIS</a>
            <a href="#footer">Year&Block: 3A</a>
        </div>
    </footer>

    <div class="footer__bar">
        Copyright © 2024 CARTCRAFT. All rights reserved.
    </div>

    <script>
        document.getElementById('filter-form').addEventListener('submit', function (e) {
            e.preventDefault();
            loadSalesData();
        });

        function loadSalesData() {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            const status = document.getElementById('status').value;

            const url = `reports.php?start-date=${startDate}&end-date=${endDate}&status=${status}`;
            window.location.href = url; 
        }

        const salesData = <?= json_encode($sales_data) ?>;
        renderSalesChart(salesData);

        function renderSalesChart(data) {
            const labels = data.map(sale => sale.order_date);
            const totalSales = data.map(sale => parseFloat(sale.total));

            const ctx = document.getElementById('salesChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Sales',
                        data: totalSales,
                        borderColor: '#f2d2ab', 
                        backgroundColor: '#fae8d2', 
                        borderWidth: 2, 
                        pointBackgroundColor: '#f2d2ab',
                        pointRadius: 4, 
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, 
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                color: '#333',
                                font: {
                                    size: 12,
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#f2d2ab',
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 12
                            },
                            displayColors: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: 'black', 
                                font: {
                                    size: 12
                                }
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(200, 200, 200, 0.3)',
                            },
                            ticks: {
                                color: '#555',
                                font: {
                                    size: 12
                                },
                                stepSize: 50,
                            },
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    </script>
    
</body>
</html>

