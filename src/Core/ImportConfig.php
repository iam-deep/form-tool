<?php

namespace Deep\FormTool\Core;

class ImportConfig
{
    protected $sampleData = null;
    protected $header = null;
    protected $headerHelp = null;

    public function __construct($sampleData, $header = null, $headerHelp = null)
    {
        $this->sampleData = $sampleData;
        $this->header = $header;
        $this->headerHelp = $headerHelp;
    }

    public function getSampleData()
    {
        return $this->sampleData;
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function getHeaderHelp()
    {
        return $this->headerHelp;
    }
}
