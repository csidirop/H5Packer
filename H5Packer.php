<?php
/**
 *  This script provides functions to create and extract H5P export archives.
 *  Usage: php H5Packer.php [pack|unpack] args...
 */

// Command-line interface:
if (php_sapi_name() == "cli") {
    if ($argc < 2) {
        printHelp();
    }

    $command = $argv[1];
    if ($command === "help" || $command === "--help" || $command === "-h") { // Option: Help message.
        printHelp();
    } elseif ($command === "pack" || $command === "repack") { // Option: Create an H5P archive.
        if ($argc != 4) { // Wrong usage
            echo "Usage: php H5Packer.php pack <sourceDir> <destinationFile>\n";
            exit(1);
        }
        $sourceDir = $argv[2];
        $destinationFile = $argv[3];
        if (createH5PArchive($sourceDir, $destinationFile)) {
            echo "H5P archive created successfully: $destinationFile\n";
        } else {
            echo "Failed to create H5P archive.\n";
        }
    } elseif ($command === "unpack") { // Option: Extract an H5P archive.
        if ($argc != 4) { // Wrong usage
            echo "Usage: php H5Packer.php unpack <archiveFile> <destinationDir>\n";
            exit(1);
        }
        $archiveFile = $argv[2];
        $destinationDir = $argv[3];
        if (extractH5PArchive($archiveFile, $destinationDir)) {
            echo "Archive extracted successfully to: $destinationDir\n";
        } else {
            echo "Failed to extract archive.\n";
        }
    } else {
        echo "Invalid command.\n";
        printHelp();
    }
}

/**
 * Prints the help message.
 * @return never
 */
function printHelp(): void {
    echo "Usage: php H5Packer.php [pack|unpack] args...\n";
    echo "  For unpacking: php H5Packer.php unpack <archiveFile> <destinationDir>\n";
    echo "  For repacking: php H5Packer.php pack <sourceDir> <destinationFile>\n";
    exit(1);
}

/**
 * Creates an H5P archive from the contents of a given directory.
 *
 * @param string $sourceDir       Path to the directory to pack.
 * @param string $destinationFile Path to the resulting H5P file.
 * @return bool
 */
function createH5PArchive($sourceDir, $destinationFile): bool {
    if (!extension_loaded('zip')) {
        die("Error: The PHP zip extension is not loaded.\n");
    }

    if (!file_exists($sourceDir)) {
        die("Error: Source directory does not exist: $sourceDir\n");
    }

    $zip = new ZipArchive();
    if ($zip->open($destinationFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        die("Error: Cannot open <$destinationFile> for writing.\n");
    }

    $sourceDir = realpath($sourceDir);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    // Count total files for progress bar
    $totalFiles = iterator_count($iterator);
    $iterator->rewind();

    // Initialize progress bar
    $processedFiles = 0;
    // echo "Packing files:\n";
    foreach ($iterator as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            // Calculate relative path to preserve directory structure inside the archive.
            $relativePath = substr($filePath, strlen($sourceDir) + 1);
            $zip->addFile($filePath, $relativePath);
            // Force using deflate compression for consistency.
            $zip->setCompressionName($relativePath, ZipArchive::CM_DEFLATE);

            // Update progress bar:
            $progress = round((++$processedFiles / $totalFiles) * 100);
            echo "\rProgress: [".str_repeat("=", $progress / 2 ).str_repeat(" ", 50 - $progress / 2)."] $progress%";
        }
    }
    echo "\nWriting to disk ...\n";

    if (!$zip->close()) {
        die("Error: Could not close the zip archive.\n");
    }
    return true;
}

/**
 * Extracts an H5P archive to a specified directory.
 *
 * @param string $archiveFile   Path to the H5P archive.
 * @param string $destinationDir Path where the archive will be extracted.
 * @return bool
 */
function extractH5PArchive($archiveFile, $destinationDir): bool {
    if (!extension_loaded('zip')) {
        die("Error: The PHP zip extension is not loaded.\n");
    }

    // Check archive file:
    if (!file_exists($archiveFile)) {
        die("Error: Archive file does not exist: $archiveFile\n");
    }
    $zip = new ZipArchive();
    if ($zip->open($archiveFile) !== TRUE) {
        die("Error: Cannot open <$archiveFile> for extraction.\n");
    }

    // Create destination directory if it does not exist or clean it up if it does:
    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0755, true);
    } else {
        cleanDir($destinationDir);
        echo "Cleaned up destination directory: $destinationDir\n";
    }

    // Start extraction:
    if (!$zip->extractTo($destinationDir)) {
        die("Error: Extraction failed.\n");
    }
    $zip->close();
    return true;
}

/**
 * Deletes all files and subdirectories in the specified directory.
 * 
 * @param mixed $dir
 * @return void
 */
function cleanDir($dir) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
        $item->isDir() ? rmdir($item) : unlink($item); 
    }
}
?>
