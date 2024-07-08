<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PWOT - Personal Work Off Tracker</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</head>

<body>
    <div class="container mt-5">
        <h1 class="text-center">PWOT - Personal Work Off Tracker</h1>
        <?php
        date_default_timezone_set('Asia/Tashkent');
        $pdo = new PDO('mysql:host=localhost;dbname=work_off_tracker', 'root', 'root');

        $records_per_page = 5;

        $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($current_page - 1) * $records_per_page;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['arrived_at']) && isset($_POST['leaved_at'])) {
                if (!empty($_POST['arrived_at']) && !empty($_POST['leaved_at'])) {
                    try {
                        $arrived_at = (new DateTime($_POST['arrived_at']))->format('Y-m-d H:i:s');
                        $leaved_at  = (new DateTime($_POST['leaved_at']))->format('Y-m-d H:i:s');

                        $start = new DateTime($_POST['arrived_at']);
                        $end = new DateTime($_POST['leaved_at']);
                        $interval = $start->diff($end);
                        $workhours = (int)$interval->format('%h');
                        $workminutes = (int)$interval->format('%i');
                        $work_duration = $workhours * 60 + $workminutes;

                        $required_work_off = 9 * 60 - $work_duration;

                        $query = "INSERT INTO daily (arrived_at, leaved_at, work_duration, Required_work_off, work_done) VALUES (:arrived_at, :leaved_at, :work_duration, :required_work_off, 0)";
                        $stmt = $pdo->prepare($query);
                        $stmt->bindParam(':arrived_at', $arrived_at);
                        $stmt->bindParam(':leaved_at', $leaved_at);
                        $stmt->bindParam(':work_duration', $work_duration);
                        $stmt->bindParam(':required_work_off', $required_work_off);
                        $stmt->execute();
                        echo '<div class="alert alert-success">Record inserted successfully.</div>';
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
                    }
                } else {
                    echo '<div class="alert alert-warning">Please fill in both inputs.</div>';
                }
            } elseif (isset($_POST['done'])) {
                try {
                    $id = $_POST['done'];
                    $query = "UPDATE daily SET work_done = 1 WHERE id = :id";
                    $stmt = $pdo->prepare($query);
                    $stmt->bindParam(':id', $id);
                    $stmt->execute();
                    echo '<div class="alert alert-success">Work marked as done.</div>';
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
                }
            } elseif (isset($_POST['export'])) {
                $query = $pdo->query("SELECT * FROM daily")->fetchAll(PDO::FETCH_ASSOC);

                $filename = "jadval.csv";

                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=' . $filename);
                $output = fopen('php://output', 'w');

                fputcsv($output, array('ID', 'Arrived At', 'Leaved At', 'Work Duration (min)', 'Required Work Off (min)', 'Work Done'));

                foreach ($query as $row) {
                    fputcsv($output, $row);
                }

                fclose($output);
                exit;
            }
        }
        ?>

        <form class="form-inline justify-content-center mb-4" action="" method="post">
            <div class="form-group mx-sm-3 mb-2">
                <label for="arrivedAt" class="sr-only">Arrived at</label>
                <input type="datetime-local" class="form-control" id="arrivedAt" name="arrived_at" required>
            </div>
            <div class="form-group mx-sm-3 mb-2">
                <label for="leftAt" class="sr-only">Left at</label>
                <input type="datetime-local" class="form-control" id="leftAt" name="leaved_at" required>
            </div>
            <button type="submit" class="btn btn-primary mb-2">Submit</button>
        </form>

        <form action="" method="post">
            <button type="submit" name="export" class="btn btn-success mb-2">Export</button>
        </form>

        <table class="table">
            <thead class="thead-dark">
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Arrived at</th>
                    <th scope="col">Leaved at</th>
                    <th scope="col">Required Work Off (min)</th>
                    <th scope="col">Worked off</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = $pdo->prepare("SELECT * FROM daily LIMIT :limit OFFSET :offset");
                $query->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
                $query->bindParam(':offset', $offset, PDO::PARAM_INT);
                $query->execute();
                $results = $query->fetchAll();

                foreach ($results as $row) {
                    echo "<tr>
                            <th scope='row'>{$row['id']}</th>
                            <td>{$row['arrived_at']}</td>
                            <td>{$row['leaved_at']}</td>
                            <td>{$row['Required_work_off']}</td>
                            <td>";
                    if ($row['work_done']) {
                        echo "<input type='checkbox' checked disabled> Done ";
                    } else {
                        echo "<button type='button' class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#exampleModal{$row['id']}'>
                                    Done
                                </button>

                                <div class='modal fade' id='exampleModal{$row['id']}' tabindex='-1' aria-labelledby='exampleModalLabel' aria-hidden='true'>
                                    <div class='modal-dialog'>
                                        <div class='modal-content'>
                                            <div class='modal-header'>
                                                <h1 class='modal-title fs-5' id='exampleModalLabel'>Confirm Action</h1>
                                                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                                            </div>
                                            <div class='modal-body'>
                                                Are you sure you want to mark this day as worked off?
                                            </div>
                                            <div class='modal-footer'>
                                                <form action='' method='post'>
                                                    <input type='hidden' name='done' value='{$row['id']}'>
                                                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                                                    <button type='submit' class='btn btn-primary'>Yes</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>";
                    }
                    echo "</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="text-right">
            <?php
       
            $total_records = $pdo->query("SELECT COUNT(*) FROM daily")->fetchColumn();
            $total_pages = ceil($total_records / $records_per_page);

            $totalWorkOffTime = 0;
            $totalWorkedOffDays = 0;
            foreach ($results as $row) {
                $totalWorkOffTime += $row['Required_work_off'];
                if ($row['Required_work_off'] <= 0 || $row['work_done']) {
                    $totalWorkedOffDays++;
                }
            }
            $totalHours = floor($totalWorkOffTime / 60);
            $totalMinutes = $totalWorkOffTime % 60;
            ?>
            <p>Total work off hours: <span id="totalWorkOffHours"><?= $totalHours ?> hours and <?= $totalMinutes ?> min.</span></p>
            <p>Total worked off days: <span id="totalWorkedOffDays"><?= $totalWorkedOffDays ?></span></p>
            
            <nav aria-label="Page navigation example">
                <ul class="pagination">
                    <?php if ($current_page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo ($current_page - 1); ?>">Previous</a></li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo ($current_page + 1); ?>">Next</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>


</body>

</html>
