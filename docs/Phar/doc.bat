REM command line:  doc.bat -c ../../phpdoc.dist.xml -v
REM %~dp0 appends the full path to phpdocumentor.phar.
@ECHO ON
php %~dp0phpdocumentor.phar %*
