<?php
// Output file name
$outputFile = 'combined_files.txt';

// Open the output file for writing
$handle = fopen($outputFile, 'w');
if ($handle === false) {
    die('Error: Unable to create output file ' . $outputFile);
}

// Write header
fwrite($handle, "KMSurveyTool PHP Files\n");
fwrite($handle, "Generated on: " . date('Y-m-d H:i:s') . "\n\n");

// Get all PHP files in the current directory
$phpFiles = glob('*.php');

// Check if any PHP files were found
if (empty($phpFiles)) {
    fwrite($handle, "No PHP files found in the current directory.\n");
} else {
    foreach ($phpFiles as $file) {
        // Skip the combiner script itself
        if ($file === 'combiner.php') {
            continue;
        }

        // Write file separator and name
        fwrite($handle, "===== File: $file =====\n");

        // Read file contents
        $content = @file_get_contents($file);
        if ($content === false) {
            fwrite($handle, "Error: Unable to read file $file\n\n");
            continue;
        }

        // Write file contents
        fwrite($handle, $content . "\n\n");
    }
}

// Write footer
fwrite($handle, "===== End of Combined Files =====");

// Close the file
fclose($handle);

echo "Combined PHP files into $outputFile successfully.";
?>