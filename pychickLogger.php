<?php

/**
 * simple logger
 *
 * @author Pychick
 */

/**
 * перечисление возможных уровней журналирования
 */
enum pychickLoggerLevel: int
{
    case TRACE = 4;
    case DEBUG = 3;
    case INFO = 2;
    case WARN = 1;
    case ERROR = 0;

    public function toString(): string
    {
        return $this->value;
    }
}

/**
 * некий класс пищущий журналы в привычном мне формате
 */
class pychickLogger
{

    /** @var false|resource поток, в который выводится журнал */
    protected $fLog;

    /** @var string имя файла журнала */
    protected string $sLogFileName;

    /** @var pychickLoggerLevel текущий уровень дебага */
    protected pychickLoggerLevel $nLevel;

    /** @var int ID подклбчения к БД при наличии */
    protected int $nDbSID = 0;

    /** @var string|mixed IP-адрес клиента или машины, на которй сиполняется скрипт */
    protected string $sIp;

    /** @var bool выводить ли дополнительно журнал в консоль. По умолчанию ИСТИНА для консольных скриптов и ЛОЖЬ для серверных */
    protected bool $bWriteToConsole;

    public function __construct(string $sFileName, pychickLoggerLevel $nLevel)
    {
        $this->nLevel=$nLevel;
        $this->sLogFileName=$sFileName;
        $this->fLog = fopen($sFileName, "a");
        if(php_sapi_name()=="cli") {
            $host= gethostname();
            $ip = gethostbyname($host);
            $this->sIp=$ip;
            $this->bWriteToConsole=true;
        }
        else {
            $this->sIp=$_SERVER["REMOTE_ADDR"];
            $this->bWriteToConsole=false;
        }

    }

    protected function logLine(pychickLoggerLevel $level, string $sMessage) : bool {

        $sOutLine=date("Y-m-d H:i:s", time());
        $sOutLine.="\t".$this->sIp."\t";

        $trace=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $sOutLine.=basename($trace[1]["file"])."\t".
            $trace[1]["line"]."\t".
            $level->toString()."/".$this->nLevel->toString()."\t".
            session_id()."\t".
            $this->nDbSID;

        $sOutLine.="\t".$sMessage.PHP_EOL;

        if(!fwrite($this->fLog, $sOutLine)) {
             /** не удалось записать в файл, возможно, он закрыт или удален, попробуем открыть его заново */
            $this->fLog = fopen($this->sLogFileName, "a");
            fwrite($this->fLog, $sOutLine);
        }

        if($this->bWriteToConsole) {
            echo $sOutLine;
        }
        return true;
    }

    public function error(string $sMessage) : bool {
        return $this->logLine(pychickLoggerLevel::ERROR, $sMessage);
    }

    public function warn(string $sMessage) : bool
    {
        if($this->nLevel>=pychickLoggerLevel::WARN) return $this->logLine(pychickLoggerLevel::WARN, $sMessage);
        else return false;
    }

    public function info(string $sMessage) : bool {
        if($this->nLevel->value>=pychickLoggerLevel::INFO->value) return $this->logLine(pychickLoggerLevel::INFO, $sMessage);
        else return false;
    }

    public function debug(string $sMessage) : bool
    {
        if($this->nLevel>=pychickLoggerLevel::DEBUG) return $this->logLine(pychickLoggerLevel::DEBUG, $sMessage);
        else return false;
    }

    public function trace(string $sMessage) : bool
    {
        if($this->nLevel>=pychickLoggerLevel::TRACE) return $this->logLine(pychickLoggerLevel::TRACE, $sMessage);
        else return false;
    }

    public function setLevel(pychickLoggerLevel $level): pychickLoggerLevel
    {
        $prevLevel=$this->nLevel;
        $this->nLevel=$level;
        return $prevLevel;
    }

    public function setDbSID(int $newDbSID): int
    {
        $prevDbSID=$this->nDbSID;
        $this->nDbSID=$newDbSID;
        return $prevDbSID;
    }
}