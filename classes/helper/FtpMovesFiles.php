<?php

/**
 * @author Vladyslav Zhyokin
 */

/**
 * @brief Helper class for FTP file operations in the BIM system.
 *
 * This class handles FTP operations for image files associated with notes
 * in the Barcode Instruction Management (BIM) system. It provides functionality
 * for connecting to an FTP server, uploading files, deleting files, and
 * properly closing connections.
 * 
 * @class Helper_FtpMovesFiles
 */
class Helper_FtpMovesFiles
{
    /**
     * @brief FTP server address
     * @var string
     * @access private
     */
    private $ftp_server = "serverpath.com";
    
    /**
     * @brief FTP username for authentication
     * @var string
     * @access private
     */
    private $ftp_username = "ftpuser";
    
    /**
     * @brief FTP password for authentication
     * @var string
     * @access private
     */
    private $ftp_userpass = "ftpuserpass";
    
    /**
     * @brief Name of the image file on the FTP server
     * @var string|null
     * @access private
     */
    private $imgName = null;
    
    /**
     * @brief Remote directory path on the FTP server
     * @var string
     * @access private
     */
    private $fileRemote = "INSTRUKCJE/BIM/MONTAGE_INSTRUCTIONS_IMG/";
    
    /**
     * @brief Local file path or file data to upload
     * @var string|null
     * @access private
     */
    private $files = null;
    
    /**
     * @brief FTP connection resource
     * @var resource|null
     * @access private
     */
    private $ftp_conn = null;
    
    /**
     * @brief Login status flag
     * @var bool|null
     * @access private
     */
    private $login = null;

    /**
     * @brief Constructs a new FTP file handler.
     *
     * Initializes the FTP handler with image name and file data, and
     * automatically establishes a connection to the FTP server.
     *
     * @param string $imgName Name to use for the file on the FTP server
     * @param string $files Local file path or file data to upload
     * @access public
     */
    public function __construct($imgName, $files)
    {
        $this->imgName = $imgName;
        $this->files = $files;
        $this->makeConnFtp();
    }

    /**
     * @brief Establishes connection to the FTP server.
     *
     * Creates an FTP connection and performs login with the configured
     * credentials. If connection or login fails, the script terminates
     * with an error message.
     *
     * @return void
     * @access private
     */
    private function makeConnFtp()
    {
        $this->ftp_conn = ftp_connect($this->ftp_server) or die("Could not connect to $this->ftp_server");
        $this->login = ftp_login($this->ftp_conn, $this->ftp_username, $this->ftp_userpass);

        if (!$this->login) {
            die("Could not login to FTP server with username $this->ftp_username");
        }
    }

    /**
     * @brief Uploads a file to the FTP server.
     *
     * Uploads the file specified during object creation to the
     * configured remote directory with the specified image name.
     *
     * @return int 1 on success, 0 on failure
     * @access public
     */
    public function ftpPut()
    {
        $fileRemote = $this->fileRemote . $this->imgName;
        if (ftp_put($this->ftp_conn, $fileRemote, $this->files, FTP_BINARY)) {
            return 1;
        } else {
            echo "Error uploading $fileRemote";
            return 0;
        }
    }

    /**
     * @brief Deletes a file from the FTP server.
     *
     * Attempts to delete the file specified during object creation
     * from the configured remote directory. Outputs status messages
     * to indicate progress and result.
     *
     * @return int 1 on success, 0 on failure
     * @access public
     */
    public function ftpDelete()
    {
        $fileRemote = $this->fileRemote . $this->imgName;
        echo "Attempting to delete file: $fileRemote<br>";

        if (ftp_delete($this->ftp_conn, $fileRemote)) {
            echo "File deleted successfully: $fileRemote<br>";
            return 1;
        } else {
            echo "Error deleting file: $fileRemote<br>";
            return 0;
        }
    }

    /**
     * @brief Closes the FTP connection.
     *
     * Safely closes the FTP connection if one exists.
     * Should be called when file operations are complete.
     *
     * @return void
     * @access public
     */
    public function ftpClose()
    {
        if ($this->ftp_conn) {
            ftp_close($this->ftp_conn);
        }
    }
}