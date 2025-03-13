# H5Packer
This script provides functions to create and extract [H5P](https://github.com/h5p) archives.

## Usage
To use the script, run it from the command line with the following options:

#### Unpack
Extracts an H5P archive to a directory:
```sh
php H5Packer.php unpack <archiveFile> <destinationDir>
```

#### Pack
Creates an H5P archive from a directory:

```sh
php H5Packer.php pack <sourceDir> <destinationFile>
```

## Requirements
[PHP](https://www.php.net/manual/en/install.php) with the zip extension enabled.

## Why?
This script uses PHP's native ZipArchive because generic command-line tools like zip or 7z don't generate the same archives. The H5P export process sets specific metadata, file ordering, and central directory details that ensure compatibility with H5P importers.

## License
This project is licensed under the GNU General Public License v3.0. See the LICENSE file for details. 