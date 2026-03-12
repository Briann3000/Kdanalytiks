<?php
// aggregator.php - Web-based PHP file aggregator
$outputFile = 'aggregated.txt';
$files = glob('*.php');
$excludeFiles = [basename(__FILE__), 'aggregated.txt']; // Exclude self and output file

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get selected files or use all files
    $selectedFiles = isset($_POST['files']) ? $_POST['files'] : array_diff($files, $excludeFiles);
    
    // Generate aggregated content
    $content = "=== PHP Files Aggregation ===\n";
    $content .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($selectedFiles as $file) {
        if (in_array($file, $excludeFiles)) continue;
        
        $content .= "=== FILE: $file ===\n\n";
        $fileContent = file_get_contents($file);
        
        if ($fileContent === false) {
            $content .= "Error: Could not read file '$file'\n\n";
        } else {
            $content .= $fileContent;
            $content .= "\n\n";
        }
    }
    
    // Save to file
    file_put_contents($outputFile, $content);
    
    // Force download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($outputFile).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($outputFile));
    readfile($outputFile);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP File Aggregator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .file-list {
            margin: 20px 0;
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
        }
        .file-item {
            margin: 8px 0;
            display: flex;
            align-items: center;
        }
        .file-item input[type="checkbox"] {
            margin-right: 10px;
        }
        .file-item label {
            cursor: pointer;
            flex-grow: 1;
        }
        .file-item label:hover {
            background-color: #f0f0f0;
        }
        .buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #545b62;
        }
        .info {
            margin-top: 20px;
            padding: 15px;
            background-color: #e7f3ff;
            border-left: 4px solid #007bff;
            border-radius: 4px;
        }
        .file-count {
            font-weight: bold;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>PHP File Aggregator</h1>
        
        <div class="info">
            This tool will aggregate all your PHP files into a single text file for easy sharing.
            Select the files you want to include or use the "Select All" button.
        </div>
        
        <form method="post">
            <div class="file-list">
                <?php foreach (array_diff($files, $excludeFiles) as $file): ?>
                    <div class="file-item">
                        <input type="checkbox" name="files[]" value="<?php echo htmlspecialchars($file); ?>" id="file_<?php echo md5($file); ?>" checked>
                        <label for="file_<?php echo md5($file); ?>"><?php echo htmlspecialchars($file); ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="buttons">
                <button type="button" class="btn-secondary" onclick="toggleAll()">Select All / None</button>
                <button type="submit" class="btn-primary">Generate Aggregated File</button>
            </div>
        </form>
        
        <div class="info">
            Found <span class="file-count"><?php echo count(array_diff($files, $excludeFiles)); ?></span> PHP files in this directory.
            The aggregator will exclude itself and the output file from the aggregation.
        </div>
    </div>

    <script>
        function toggleAll() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });
        }
    </script>
</body>
</html>