<!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Notes Wireframe</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                /* HIDE SCROLLBAR */
                body, html {
                    overflow: hidden;
                    overflow-y: scroll;
                    scrollbar-width: none;
                    -ms-overflow-style: none;
                }

                body::-webkit-scrollbar, html::-webkit-scrollbar {
                    display: none;
                }
                /* HIDE SCROLLBAR */
                
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f0f0f0;
                    padding: 20px;
                }

                .container {
                    display: flex;
                    flex-direction: row;
                    width: 100%;
                    max-width: 1200px;
                    margin: 0 auto;
                    background: white;
                    border: 1px solid #ccc;
                    border-radius: 8px;
                    overflow: hidden;
                }

                .sidebar {
                    width: 20%;
                    background: #e8e8e8;
                    padding: 20px;
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                }

                .sidebar button {
                    background: none;
                    border: 1px solid #ccc;
                    padding: 10px;
                    text-align: left;
                    cursor: pointer;
                    border-radius: 4px;
                    transition: background 0.2s;
                }

                .sidebar button:hover {
                    background: #ddd;
                }

                .main-content {
                    flex-grow: 1;
                    padding: 20px;
                }

                .header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                }

                .header input[type="search"] {
                    padding: 10px;
                    border: 1px solid #ccc;
                    border-radius: 4px;
                    width: 50%;
                }

                .notes-section {
                    display: flex;
                    gap: 20px;
                }

                .notes-list {
                    width: 50%;
                }

                .notes-list .note, td {
                    border: 1px solid #ccc;
                    border-radius: 4px;
                    padding: 10px;
                    margin-bottom: 10px;
                    background: #fafafa;
                }
                
                .notes-list td:hover {
                    background: #ddd;
                }

                table {
                    width: 100%;
                }

                .write-section {
                    position: relative;
                    width: 50%;
                    border: 1px solid #ccc;
                    border-radius: 4px;
                    padding: 20px;
                    background: #fafafa;
                }

                .write-section h2 {
                    margin: 0;
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                }

                .write-section input, .write-section h3 {
                    width: 100%;
                    margin-bottom: 10px;
                }

                .write-section textarea {
                    width: 100%;
                    height: 85%;
                }

                .notes-list-header {
                    display: flex;
                }

                .notes-list-header p {
                    font-size: 1.5em;
                }
            </style>
        </head>
        
        <?php
        $host = 'localhost';
        $db = 'project';
        $user = 'root';
        $pass = '';
        $charset = 'utf8mb4';

        try {
            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $pdo = new PDO($dsn, $user, $pass);
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
            exit;
        }

        $title = $content = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['title']) && isset($_POST['content'])) {
                $title = $_POST['title'];
                $content = $_POST['content'];
                
                $sql = "INSERT INTO notes (note_title, note_content) VALUES (:title, :content)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['title' => $title, 'content' => $content]);
                echo "<br>Data inserted successfully!<hr>";
            }

            if (isset($_POST['delete_note_id'])) {
                $nid = $_POST['delete_note_id'];
                $sql = "DELETE FROM notes WHERE note_id = :nid";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['nid' => $nid]);
                echo "<br>Data deleted successfully!<hr>";
            }

            if (isset($_POST['update_note_id']) && isset($_POST['updated_title']) && isset($_POST['updated_content'])) {
                $nid = $_POST['update_note_id'];
                $title = $_POST['updated_title'];
                $content = $_POST['updated_content'];

                $sql = "UPDATE notes SET note_title = :title, note_content = :content WHERE note_id = :nid";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['title' => $title, 'content' => $content, 'nid' => $nid]);
            }
        }

        $sql = "SELECT note_id, note_title, note_content FROM notes";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll();

        if (isset($_GET['note'])) {
            $nid = $_GET['note'];
            $sql2 = "SELECT note_title, note_content FROM notes WHERE note_id = :nid";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute(['nid' => $nid]);
            $datas = $stmt2->fetchAll();
        }

        $title = $content = "";
        if (isset($_GET['edit'])) {
            $nid = $_GET['edit'];
            $sql = "SELECT note_title, note_content FROM notes WHERE note_id = :nid";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['nid' => $nid]);
            $row = $stmt->fetch();

            if ($row) {
                $title = $row['note_title'];
                $content = $row['note_content'];
            }
        }
        ?>
        
        <body>
            <div class="container">
                <div class="sidebar">
                    <!-- NEW PAGE -->
                    <?php if (isset($_GET['newpage'])): ?>
                        <button type="button" onclick="submitData()">Save</button>
                        <button onclick="window.location.href='/infosec_project/'">Cancel</button>

                    <!-- NOTES -->
                    <?php elseif (isset($_GET['note'])): ?>
                        <button type="button" onclick="window.location.href='/infosec_project/?edit=<?= $_GET['note'] ?>'">Edit</button>
                        <button type="button" onclick="deleteData(<?= $_GET['note'] ?>)">Delete</button>
                        <button onclick="window.location.href='/infosec_project/'">Back</button>

                    <!-- EDIT -->
                    <?php elseif (isset($_GET['edit'])): ?>
                        <button type="button" onclick="updateData(<?= $_GET['edit'] ?>)">Save</button>
                        <button type="button" onclick="window.location.href='/infosec_project/?note=<?= $_GET['edit'] ?>'">Cancel</button>

                    <!-- DEFAULT -->
                    <?php else: ?>
                        <button onclick="window.location.href='?newpage=true'">New Page</button>
                    <?php endif; ?>
                </div>

                <div class="main-content">
                    <div class="header">
                        <input type="search" placeholder="Search">
                    </div>

                    <div class="notes-section">
                        <div class="notes-list">
                            <div class="notes-list-header">
                                <div style="flex-grow: 1">
                                    <h2>Notes</h2>
                                </div>

                                <div style="flex-grow: 1; text-align: right;">
                                    <p>Sort By</p>
                                </div>
                            </div>
                            <table>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                    <td onclick="window.location.href='?note=' + <?= $row['note_id']; ?>;">
                                        <h3><?= htmlspecialchars($row['note_title']); ?></h3>
                                        <br>
                                        <?= htmlspecialchars(substr($row['note_content'], 0, 40)) . (strlen($row['note_content']) > 40 ? '...' : ''); ?>
                                    </td>
                                    </tr>
                                <?php endforeach ?>
                            </table>
                        </div>

                        <!-- NEW PAGE -->
                        <?php if (isset($_GET['newpage'])): ?>
                            <div class="write-section">
                                <input type="text" id="Title" placeholder="Title" required>
                                <textarea id="Content" required></textarea>
                            </div>

                        <!-- NOTES -->
                        <?php elseif (isset($_GET['note'])): ?>
                            <div class="write-section">
                                <?php foreach ($datas as $data): ?>
                                    <h3><?= $data['note_title']?></h3>
                                    <p style="word-wrap: break-word; overflow-wrap: break-word; max-width: 390px;"><?= $data['note_content']?></p>
                                <?php endforeach ?>
                            </div>

                        <!-- EDIT -->
                        <?php elseif (isset($_GET['edit'])): ?>
                            <div class="write-section">
                                <input type="text" id="Title" value="<?php echo htmlspecialchars($title); ?>" required>
                                <textarea id="Content" required><?php echo htmlspecialchars_decode($content); ?></textarea>
                            </div>

                        <!-- DEFAULT -->
                        <?php else: ?>
                            <div class="write-section">
                                <h2>Write your ideas</h2>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </body>
    </html>

    <script>
        function submitData() {
            const titleInput = document.getElementById('Title').value;
            const contentInput = document.getElementById('Content').value;

            const formData = new FormData();
            formData.append('title', titleInput);
            formData.append('content', contentInput);

            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                console.log('Success:', result);
                window.location.href = '/infosec_project/';
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function deleteData(noteId) {
            const formData = new FormData();
            formData.append('delete_note_id', noteId);

            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                console.log('Success:', result);
                window.location.href = '/infosec_project/';
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function updateData(editId) {
            const titleInput = document.getElementById('Title').value;
            const contentInput = document.getElementById('Content').value;

            const formData = new FormData();
            formData.append('update_note_id', editId);
            formData.append('updated_title', titleInput);
            formData.append('updated_content', contentInput);

            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                console.log('Success:', result);
                window.location.href = '/infosec_project/';
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
