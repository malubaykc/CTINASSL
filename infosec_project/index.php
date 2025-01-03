<!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Notes App</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                /* ↓↓ HIDE SCROLLBAR ↓↓ */
                body, html {
                    overflow: hidden;
                    overflow-y: scroll;
                    scrollbar-width: none;
                    -ms-overflow-style: none;
                }

                body::-webkit-scrollbar, html::-webkit-scrollbar {
                    display: none;
                }
                /* ↑↑ HIDE SCROLLBAR ↑↑ */

                body {
                    font-family: Arial, sans-serif;
                    background-color: #f0f0f0;
                    padding: 20px;
                }

                .container {
                    display: flex;
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
                    transition: background 0.5s;
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
            // ↓↓ Establish DB Connection ↓↓ \\
            $host = 'localhost';
            $db = 'project';
            $table = 'notes';
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
            // ↑↑ Establish DB Connection ↑↑ \\

            // ↓↓ $_POST for INSERTING, DELETING, and UPDATING ↓↓ \\
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $queries = [
                    'insert' => [
                        'condition' => isset($_POST['title'], $_POST['content']),
                        'sql' => "INSERT INTO $table (note_title, note_content) VALUES (:title, :content)",
                        'params' => ['title' => $_POST['title'], 'content' => $_POST['content']],
                    ],
                    'delete' => [
                        'condition' => isset($_POST['delete_note_id']),
                        'sql' => "DELETE FROM $table WHERE note_id = :nid",
                        'params' => ['nid' => $_POST['delete_note_id']],
                    ],
                    'update' => [
                        'condition' => isset($_POST['update_note_id'], $_POST['updated_title'], $_POST['updated_content']),
                        'sql' => "UPDATE $table SET note_title = :title, note_content = :content WHERE note_id = :nid",
                        'params' => [
                            'title' => $_POST['updated_title'],
                            'content' => $_POST['updated_content'],
                            'nid' => $_POST['update_note_id'],
                        ],
                    ],
                ];
            
                foreach ($queries as $query) {
                    if ($query['condition']) {
                        $stmt = $pdo->prepare($query['sql']);
                        $stmt->execute($query['params']);
                        break;
                    }
                }
            }
            // ↑↑ $_POST for INSERTING, DELETING, and UPDATING ↑↑ \\

            // ↓↓ LOAD NOTES LIST ↓↓ \\
            $search = isset($_GET['search']) && $_GET['search'] != "" ? $_GET['search'] : null;
            $sort = $_GET['sort'] ?? 'id_asc';

            $orderBy = match ($sort) {
                'id_asc' => 'note_id ASC',
                'id_desc' => 'note_id DESC',
                't_asc' => 'note_title ASC',
                't_desc' => 'note_title DESC',
                default => null,
            };

            if ($search) {
                $sql = "SELECT note_id, note_title, note_content FROM $table WHERE note_title LIKE :search";
                $sql .= $orderBy ? " ORDER BY $orderBy" : "";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['search' => "%$search%"]);
            } else {
                $sql = "SELECT note_id, note_title, note_content FROM $table";
                $sql .= $orderBy ? " ORDER BY $orderBy" : "";
                $stmt = $pdo->query($sql);
            }

            $rows = $stmt->fetchAll();
            // ↑↑ LOAD NOTES LIST ↑↑ \\

            // ↓↓ LOAD SELECTED NOTES ↓↓ \\
            if (!empty($_GET['note'])) {
                $nid = $_GET['note'];
                $sql = "SELECT note_title, note_content FROM notes WHERE note_id = :nid";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['nid' => $nid]);
                $datas = $stmt->fetchAll();
            }            
            // ↑↑ LOAD SELECTED NOTES ↑↑ \\

            // ↓↓ EDIT SELECTED NOTES ↓↓ \\
            if (!empty($_GET['edit'])) {
                $nid = $_GET['edit'];
                $sql = "SELECT note_title, note_content FROM $table WHERE note_id = :nid";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['nid' => $nid]);
                $row = $stmt->fetch();
            
                [$title, $content] = $row ? [$row['note_title'], $row['note_content']] : ["", ""];
            }            
            // ↑↑ EDIT SELECTED NOTES ↑↑ \\
        ?>
        
        <body>
            <div class="container">
                <!-- ↓↓ SIDE BAR BUTTONS ↓↓ -->
                <div class="sidebar">
                    <!-- NEW PAGE -->
                    <?php if (isset($_GET['newpage'])): ?>
                        <button type="button" onclick="submitData()">Save</button>
                        <button onclick="window.location.href='/infosec_project/'">Cancel</button>

                    <!-- NOTE -->
                    <?php elseif (isset($_GET['note'])): ?>
                        <button type="button" onclick="editData(<?= $_GET['note'] ?>)">Edit</button>
                        <button type="button" onclick="deleteData(<?= $_GET['note'] ?>)">Delete</button>
                        <button onclick="back()">Back</button>

                    <!-- EDIT -->
                    <?php elseif (isset($_GET['edit'])): ?>
                        <button type="button" onclick="updateData(<?= $_GET['edit'] ?>)">Save</button>
                        <button onclick="cancelEdit(<?= $_GET['edit'] ?>)">Cancel</button>

                    <!-- DEFAULT -->
                    <?php else: ?>
                        <button onclick="window.location.href='?newpage=true'">New</button>
                    <?php endif; ?>
                </div>
                <!-- ↑↑ SIDE BAR BUTTONS ↑↑ -->

                <!-- ↓↓ MAIN CONTENTS ↓↓ -->
                <div class="main-content">

                    <!-- ↓↓ HEADER ↓↓ -->
                    <div class="header">
                        <input type="search" id="searchInput" placeholder="Search" value="<?= isset($_GET['search']) ? $_GET['search'] : ''; ?>">
                    </div>
                    <!-- ↑↑ HEADER ↑↑ -->

                    <div class="notes-section">
                        <!-- ↓↓ NOTES SECTION ↓↓ -->
                        <div class="notes-list">
                            <!-- ↓↓ NOTES HEADER & SORTER ↓↓-->
                            <div class="notes-list-header">
                                <div style="flex-grow: 1">
                                    <h2>Notes</h2>
                                </div>

                                <div style="flex-grow: 1; text-align: right;">
                                    <form>
                                        <label for="sort">Sort By:</label>
                                        <select name="sort" id="sort" onchange="sortBy(this)">
                                            <option value="id_asc" <?= ($_GET['sort'] ?? '') === 'id_asc' ? 'selected' : ''; ?>>ID↑</option>
                                            <option value="id_desc" <?= ($_GET['sort'] ?? '') === 'id_desc' ? 'selected' : ''; ?>>ID↓</option>
                                            <option value="t_asc" <?= ($_GET['sort'] ?? '') === 't_asc' ? 'selected' : ''; ?>>Title↑</option>
                                            <option value="t_desc" <?= ($_GET['sort'] ?? '') === 't_desc' ? 'selected' : ''; ?>>Title↓</option>
                                        </select>
                                    </form>
                                </div>
                            </div>
                            <!-- ↑↑ NOTES HEADER & SORTER ↑↑-->

                            <!-- ↓↓ NOTES LIST ↓↓ -->
                            <table>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                    <td onclick="window.location.href='?note=<?= $row['note_id']; ?><?php if (isset($_GET['sort']) && $_GET['sort'] != 'id_asc'): ?>&sort=<?= htmlspecialchars($_GET['sort']); ?><?php endif;if (isset($_GET['search']) && $_GET['search'] != ''): ?>&search=<?= htmlspecialchars($_GET['search']); ?><?php endif ?>'">
                                            <h3><?= htmlspecialchars($row['note_title']); ?></h3>
                                            <br>
                                            <?= htmlspecialchars(substr($row['note_content'], 0, 40)) . (strlen($row['note_content']) > 40 ? '...' : ''); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                            <!-- ↑↑ NOTES LIST ↑↑ -->
                        </div>
                        <!-- ↑↑ NOTES SECTION ↑↑ -->

                        <!-- ↓↓ WRITE SECTION ↓↓ -->
                        <?php if (isset($_GET['newpage'])): ?>
                            <!-- NEW PAGE -->
                            <div class="write-section">
                                <input type="text" id="Title" placeholder="Title" required>
                                <textarea id="Content" required></textarea>
                            </div>

                        <?php elseif (isset($_GET['note'])): ?>
                            <!-- NOTES -->
                            <div class="write-section">
                                <?php foreach ($datas as $data): ?>
                                    <h3><?= $data['note_title']?></h3>
                                    <p style="word-wrap: break-word; overflow-wrap: break-word; max-width: 390px;"><?= $data['note_content']?></p>
                                <?php endforeach ?>
                            </div>

                        <?php elseif (isset($_GET['edit'])): ?>
                            <!-- EDIT -->
                            <div class="write-section">
                                <input type="text" id="Title" value="<?php echo htmlspecialchars($title); ?>" required>
                                <textarea id="Content" required><?php echo htmlspecialchars_decode($content); ?></textarea>
                            </div>

                        <?php else: ?>
                            <!-- DEFAULT -->
                            <div class="write-section">
                                <h2>Write your ideas</h2>
                            </div>
                        <?php endif; ?>
                        <!-- ↑↑ WRITE SECTION ↑↑ -->
                    </div>
                </div>
            </div>
            <!-- ↑↑ MAIN CONTENTS ↑↑ -->

        </body>
    </html>

    <script>
        // ↓↓ UPLOAD NOTE ↓↓ \\
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
        // ↑↑ UPLOAD NOTE ↑↑ \\

        // ↓↓ DELETE NOTE ↓↓ \\
        function deleteData(noteId) {
            const formData = new FormData();
            const url = new URL(window.location.href);
            formData.append('delete_note_id', noteId);

            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                console.log('Success:', result);
                url.searchParams.delete('note');
                window.location.href = url.toString();
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        // ↑↑ DELETE NOTE ↑↑ \\

        // ↓↓ UPDATE NOTE ↓↓ \\
        function updateData(editId) {
            const titleInput = document.getElementById('Title').value;
            const contentInput = document.getElementById('Content').value;
            const url = new URL(window.location.href);

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
                url.searchParams.delete('edit');
                url.searchParams.set('note', editId);
                window.location.href = url.toString();
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        // ↑↑ UPDATE NOTE ↑↑ \\

        // ↓↓ ENABLE EDITING ↓↓ \\
        function editData(editID) {
            const url = new URL(window.location.href);
            url.searchParams.delete('note');
            url.searchParams.set('edit', editID);
            window.location.href = url.toString();
        }
        // ↑↑ ENABLE EDITING ↑↑ \\

        // ↓↓ CANCEL EDITING ↓↓ \\
        function cancelEdit(noteID) {
            const url = new URL(window.location.href);
            url.searchParams.delete('edit');
            url.searchParams.set('note', noteID);
            window.location.href = url.toString();
        }
        // ↑↑ CANCEL EDITING ↑↑ \\

        // ↓↓ BACK FUNCTION ↓↓ \\
        function back() {
            const url = new URL(window.location.href);
            url.searchParams.delete('note');
            window.location.href = url.toString();
        }
        // ↑↑ BACK FUNCTION ↑↑ \\

        // ↓↓ SORT NOTES LIST ↓↓ \\
        function sortBy(selectElement) {
            const selectedValue = selectElement.value.trim();

            const url = new URL(window.location.href);
            if (selectedValue != "id_asc") {
                url.searchParams.set('sort', selectedValue);
            } else {
                url.searchParams.delete('sort');
            }
            window.location.href = url.toString();
        }
        // ↑↑ SORT NOTES LIST ↑↑ \\

        // ↓↓ SEARCH NOTES LIST ↓↓ \\
        const input = document.getElementById('searchInput');
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {

                const url = new URL(window.location.href);
                if (event.target.value != "") {
                    url.searchParams.set('search', event.target.value);
                } else {
                    url.searchParams.delete('search');
                }
                window.location.href = url.toString();
            }
        });
        // ↑↑ SEARCH NOTES LIST ↑↑ \\
    </script>
