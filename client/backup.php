<?php

@ini_set("max_execution_time", 300);
@ini_set("memory_limit", "256M");

/**
 * SETTINGS
 *
 * Change the following to suit your needs
 */

// The name of the backup
$application = 'myApplication';

 // Credentials for the database to export
$credentials = (object)[
    'host' => 'localhost',
    'database' => 'dbname',
    'user' => 'dbuser',
    'password' => 'dbpassword',
    'port' => 3306
];

// Credentials for the FTP host
$ftpCredentials = (object)[
    'host' => 'ftp.host.com',
    'user' => 'ftpuser',
    'password' => 'ftppassword'
];

// List any folders to copy into the backup, relative to this path
// You can pick single files and elements from the parent directory as well
$includedFolders = [
    './directory1',
    '../directory2',
    '../index.php',
    '../settings.php'
];

// Should the database backup be included?
$includeDatabase = true;

// Should the backup files be uploaded?
// Otherwise they will be stored in the same folder as the backup script
$enableUpload = true;

// Path on the FTP server, where the backups will be saved
$ftpBase = "backups/";

// Chunk size - After how many entries should there be an info? -1 for never!
$chunkSize = 100;

$backupName = "backup_{$application}_" . date("Y-m-d-His");

if (file_exists($backupName)) {
    echo "Backup folder not available" . PHP_EOL;
    exit(1);
}

echo "Creating Backup in {$backupName}" . PHP_EOL;

/**
 * Create a temporary config file, that is used
 * by mysqldumper to authenticate to the database.
 * Prevents warnings from mysqldumper, if the password
 * is put in via a shell argument on launch.
 *
 * @param stdClass $credentials
 * @return void
 */
function createTempCredentialsFile($credentials) {
    $tmpFileHandle = tmpfile();
    $contents = [
        '[client]',
        "user = '{$credentials->user}'",
        "password = '{$credentials->password}'",
        "host = '{$credentials->host}'",
        "port = '{$credentials->port}'",
    ];
    $fileContent = implode(PHP_EOL, $contents);

    fwrite($tmpFileHandle, $fileContent);

    return $tmpFileHandle;
}

/**
 * Add a file or complete folder (recursively) to the zip archive
 *
 * @param ZipArchive $zip
 * @param string $path
 * @return void
 */
function addToZip(ZipArchive $zip, $path) {
    global $chunkSize;

    $rootPath = realpath($path);
    if (is_file($rootPath)) {
        $zip->addFile($rootPath, basename($path));
        return;
    }

    $directory = basename($path);

    echo "Adding $directory" . PHP_EOL;

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY);

    $chunkPosition = 0;

    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();

            $relativePath = substr($filePath, strlen($rootPath) + 1);

            $zip->addFile($filePath, $directory . '/' . $relativePath);

            if ($chunkSize >= 0) {
                if ($chunkPosition === 0) {
                    echo "I'm packing..." . PHP_EOL;
                }

                $chunkPosition = ($chunkPosition + 1) % $chunkSize;
            }
        }
    }
}

// Create database backup
if ($includeDatabase) {
    $tmpFileHandle = createTempCredentialsFile($credentials);
    $credentialFile = stream_get_meta_data($tmpFileHandle)['uri'];

    $output = [];
    $returnValue = 1;
    exec("mysqldump --defaults-extra-file=\"{$credentialFile}\" --result-file=\"{$backupName}.sql\" {$credentials->database}", $output, $returnValue);

    if ($returnValue === 0) {
        echo "Database Backup: SUCCESS" . PHP_EOL;
    } else {
        echo "Database Backup: FAIL" . PHP_EOL;
        exit(1);
    }
}

// Create Zip Archive of selected files

$zip = new ZipArchive();
$zip->open("$backupName.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);

foreach ($includedFolders as $folder) {
    addToZip($zip, $folder);
}

if ($includeDatabase) {
    $zip->addFile("$backupName.sql");
}

echo "Finishing zipping..." . PHP_EOL;
$zip->close();

echo "Creating backup archive: SUCCESS" . PHP_EOL;

if ($includeDatabase) {
    unlink("$backupName.sql");
}

// Upload the backup if selected

if (!$enableUpload) {
    exit(0);
}

echo "Uploading backup to FTP server..." . PHP_EOL;

$ftpConn = ftp_connect($ftpCredentials->host);

if ($ftpConn === false) {
    echo "Connection to backup server could not be opened" . PHP_EOL;
    exit(1);
}

if (!@ftp_login($ftpConn, $ftpCredentials->user, $ftpCredentials->password)) {
    echo "FTP Auth at backup server failed." . PHP_EOL;
    exit(1);
}

if (!ftp_put($ftpConn, "{$ftpBase}$backupName.zip", "$backupName.zip", FTP_ASCII)) {
    echo "Save Backup to remote server: FAILED" . PHP_EOL;
    exit(1);
}

echo "Save Backup to remote server: SUCCESS" . PHP_EOL;

ftp_close($ftpConn);

unlink("$backupName.zip");

echo "Backup: SUCCESS" . PHP_EOL;