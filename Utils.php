<?php
namespace XLiteTest\Framework\Web;

use XLiteTest\Framework\Config;

class Utils
{
    const DIR_PERM  = 0777;
    const FILE_PERM = 0666;
    const READ_ONLY_PERM = 0644;

        public static function restoreDB($full)
    {

        $base_dir_path = Utils::getBaseDir();
        $output_dir_path = $base_dir_path . "output";
        $tmp_dir = "backup";
        $tmp_dir_path = $output_dir_path . "/" . $tmp_dir;

        $src_var_datacache_dir_path = $base_dir_path . "/src/var/datacache";

        if ($full === true) {

            chdir( $tmp_dir_path);

            $dbuser = Config::getInstance()->getOptions('Credentials', 'dbuser');
            $dbpass = Config::getInstance()->getOptions('Credentials', 'dbpass');
            $dbname = Config::getInstance()->getOptions('Credentials', 'dbname');

            $cmd = "mysql -u $dbuser -p$dbpass $dbname < " . $tmp_dir_path. "/dump_test.sql";
            $res = exec($cmd);

        } else {

            Utils::divideDump($tmp_dir_path. "/dump_test.sql", $tmp_dir_path,$base_dir_path,100000000);

        }

        Utils::removeDir($src_var_datacache_dir_path);
    }

    public static function backupDB()
    {

        $base_dir_path = Utils::getBaseDir();
        $output_dir_path = $base_dir_path . "output";
        $tmp_dir = "backup";
        $tmp_dir_path = $output_dir_path . "/" . $tmp_dir;

        chdir($tmp_dir_path);

        $dbuser = Config::getInstance()->getOptions('Credentials', 'dbuser');
        $dbpass = Config::getInstance()->getOptions('Credentials', 'dbpass');
        $dbname = Config::getInstance()->getOptions('Credentials', 'dbname');

        $cmd = "mysqldump -u $dbuser -p$dbpass $dbname >" . $tmp_dir_path. "/dump_test.sql";
        $res = exec($cmd);

    }

    public static function divideDump($origFileName = 'dump_test.sql', $outputFolder,$base_dir_path,$maxNewDumpSize)
    {
        chdir($base_dir_path . '/.dev/build/tools');

        $cmd = "sh mysqldumpsplitter.sh --source $origFileName --decompression none --extract ALLTABLES --compression none --output_dir " . $outputFolder . '/out';

        exec($cmd);

        chdir($outputFolder . '/out');
        exec('rm -rf .sql');

        $directory = $outputFolder . '/out';

        $arrayDeleteDB = array("xc_sessions.sql", "xc_session_cells.sql");

        self::deletFile($directory,$arrayDeleteDB);

        $sql_files = self::dirlist(getcwd());

        $filesToDump = [];
        $dumpSize = 0;
        $dumpIndex = 1;

        foreach($sql_files as $file) {
            $newDumpSize = $dumpSize + filesize($file);
            if ($newDumpSize <= $maxNewDumpSize) { //1000000
                $dumpSize = $newDumpSize;
            } else {
                $dumpIndex++;
                $dumpSize = filesize($file);
            }

            $filesToDump[$dumpIndex][] = $file;
        }

        foreach($filesToDump as $index => $files) {
            $dumpName = $outputFolder . DIRECTORY_SEPARATOR . "dump_$index.sql";
            exec("cat " . implode(" ", $files) . " > $dumpName");
            //self::cleanupDump($dumpName);
        }

        //$base_dir_path = $base_dir;
        $output_dir_path = $base_dir_path . "output";
        $tmp_dir = "backup";
        $tmp_dir_path = $output_dir_path . "/" . $tmp_dir;

        chdir($tmp_dir_path);

        $dbuser = Config::getInstance()->getOptions('Credentials', 'dbuser');
        $dbpass = Config::getInstance()->getOptions('Credentials', 'dbpass');
        $dbname = Config::getInstance()->getOptions('Credentials', 'dbname');

        $cmd = "mysql -u $dbuser -p$dbpass $dbname < " . $tmp_dir_path. "/dump_$index.sql";
        exec($cmd);
        //unlink($origFileName);
    }

    public static function removeDir( $path ) {
        if ( file_exists( $path ) AND is_dir( $path ) ) {
            $dir = opendir($path);
            while ( false !== ( $element = readdir( $dir ) ) ) {
                if ( $element != '.' AND $element != '..' )  {
                    $tmp = $path . '/' . $element;
                    chmod( $tmp, 0777 );
                    if ( is_dir( $tmp ) ) {
                        Utils::removeDir( $tmp );
                    } else {
                        unlink( $tmp );
                    }
                }
            }
            closedir($dir);
            if ( file_exists( $path ) ) {
                rmdir( $path );
            }
        }
    }

    public static function getBaseDir()
    {
        $cwd = getcwd();
        preg_match('/(.*)\.dev/',$cwd, $out);
        #base_dir is usually something like "/var/www/local.dev/next"
        return $base_dir_path = $out[1];
    }

    public static function dirlist($folder)
    {
        $dir = new \DirectoryIterator($folder);
        $fileList = array();
        foreach ($dir as $fileInfo) {
            if (!$fileInfo->isDot()) {
                array_push($fileList, $fileInfo->getPathName());
            }
        }

        return $fileList;
    }

    function deletFile($directory,$arrayDeleteDB)
    {
        $dir = opendir($directory);
        while (($file = readdir($dir))) {
            if ((is_file("$directory/$file"))) {
                if (in_array("$file", $arrayDeleteDB)) {
                    unlink("$directory/$file");
                }
            }
        }
        closedir($dir);
    }

}