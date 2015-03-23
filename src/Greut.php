<?php

/**
 * Se mettre d'accord sur la doc.
 */

namespace Camael24;

use Hoa\Http\Response\Response;
use Hoa\Stream\IStream\Out;
use Hoa\Core\Exception\Exception;

class Greut
{
    protected $_out = null;
    protected $_router = null;
    protected $_data = null;
    protected $_paths = null;
    protected $_inherits = array();
    protected $_blocks = array();
    protected $_blocknames = array();
    protected $_file = '';
    protected $_headers = array();
    protected $_useHeader = true;

    public function __construct(Out $response = null, $useHeader = true)
    {
        if ($response === null) {
            $response = new Response();
        }


        $this->_useHeader = $useHeader;
        $this->_out   = $response;
        $this->_data  = new \Stdclass();
    }

    public function useHeader($bool = true)
    {
        $this->_useHeader = $bool;
    }

    public function setOutputStream(Out $response)
    {
        $this->_out = $response;
    }

    public function getOutputStream()
    {
        return $this->_out;
    }

    public function getData()
    {
        return $this->_data;
    }

    public function setData($data)
    {
        $this->_data = $data;
    }

    public function setPath($path)
    {
        if ($path[strlen($path) - 1] !== '/') {
            $path .= '/';
        }

        $this->_paths = $path;

        return $this;
    }

    public function inherits($path)
    {
        $this->_inherits[$this->_file][] = $path;
    }

    public function block($blockname, $mode = "replace")
    {
        $this->_blocknames[] = array($blockname, $mode);
        ob_start("mb_output_handler");
    }

    public function endblock()
    {
        list($blockname, $mode) = array_pop($this->_blocknames);

        if (!isset($this->_blocks[$blockname]) && $mode !== false) {
            $this->_blocks[$blockname] = array("content" => ob_get_contents(), "mode" => $mode);
        } else {
            switch ($this->_blocks[$blockname]["mode"]) {
                case "before":
                case "prepend":
                    $this->_blocks[$blockname] = array(
                        "content" => $this->_blocks[$blockname]["content"].ob_get_contents(),
                        "mode"    => $mode,
                    );
                    break;
                case "after":
                case "append":
                    $this->_blocks[$blockname] = array(
                        "content" => ob_get_contents().$this->_blocks[$blockname]["content"],
                        "mode"    => $mode,
                    );
                    break;
            }
        }

        ob_end_clean();

        if ($mode === "replace") {
            echo $this->_blocks[$blockname]["content"];
        }
    }

    public function getFilenamePath($filename)
    {
        if (preg_match('#^(?:[/\\\\]|[\w]+:([/\\\\])\1?)#', $filename) !== 1) {
            $filename = $this->_paths.$filename;
        }

        $realpath = realpath(resolve($filename, false)); // We need to use resolve beacause realpath dont use stream wrapper

        if ((false === $realpath) || !(file_exists($realpath))) {
            throw new Exception('Path '.$filename.' ('.(($realpath === false) ? 'false' : $realpath).') not found!');
        }

        return $realpath;
    }

    public function render()
    {
        if($this->_useHeader === true) {
            while ($h = array_pop($this->_headers)) {
                $this->_out->sendHeader($h[0], $h[1], $h[2], $h[3]);
            }
        }

        $this->_out->writeAll($this->renderFile($this->_file));
    }

    public function getHeaders()
    {
        return $this->_headers;
    }

    public function httpHeader($hName, $hValue, $force = true, $status = null)
    {
        $this->_headers[] = array(
            $hName,
            $hValue,
            $force,
            $status,
        );
    }

    public function setViewFile($filename)
    {
        $this->_file = $filename;

        return $this;
    }

    public function renderFile($filename)
    {
        $filename                   = $this->getFilenamePath($filename);
        $this->_file                = $filename;
        $this->_inherits[$filename] = array();
        // used by the placeholder

        ob_start("mb_output_handler");
        extract((array) $this->_data);
        include $filename;

        // restore args
        $content = ob_get_contents();
        ob_end_clean();

        while ($inherit = array_pop($this->_inherits[$filename])) {
            $content = $this->renderFile($inherit);
        }

        return $content;
    }
}
