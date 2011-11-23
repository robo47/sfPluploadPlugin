<?php

/**
 * sfPlupload actions.
 *
 * @package    video.iostudio.com
 * @subpackage sfPlupload
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class sfPluploadActions extends sfActions
{

  /**
   * Process a file upload
   *
   * @param sfWebRequest $request
   */
  public function executeUpload(sfWebRequest $request)
  {
    $this->getResponse()->setHttpHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT');
    $this->getResponse()->setHttpHeader('Last-Modified', gmdate("D, d M Y H:i:s") . ' GMT');
    $this->getResponse()->setHttpHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
    $this->getResponse()->setHttpHeader('Cache-Control', 'post-check=0, pre-check=0', false);
    $this->getResponse()->setHttpHeader('Pragma', 'no-cache');

    set_time_limit(5 * 60);

    $targetDir = sfConfig::get('sf_upload_dir');

    $chunk = $request->getParameter('chunks', 0);
    $chunks = $request->getParameter('chunk', 0);
    $fileName = $request->getParameter('name','');

    $fileName = preg_replace('/[^\w\._]+/', '', $fileName);

    // Make sure the fileName is unique but only if chunking is disabled
    if($chunks < 2 && file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName))
    {
      $ext = strrpos($fileName, '.');
      $fileName_a = substr($fileName, 0, $ext);
      $fileName_b = substr($fileName, $ext);

      $count = 1;
      while(file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b))
        $count++;

      $fileName = $fileName_a . '_' . $count . $fileName_b;
    }

    // Look for the content type header
    $contentType = '';
    if(isset($_SERVER["HTTP_CONTENT_TYPE"]))
      $contentType = $_SERVER["HTTP_CONTENT_TYPE"];

    if(isset($_SERVER["CONTENT_TYPE"]))
      $contentType = $_SERVER["CONTENT_TYPE"];

    // Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
    if(strpos($contentType, "multipart") !== false)
    {
      if(isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name']))
      {
        // Open temp file
        $out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
        if($out)
        {
          // Read binary input stream and append it to temp file
          $in = fopen($_FILES['file']['tmp_name'], "rb");

          if($in)
          {
            while($buff = fread($in, 4096))
              fwrite($out, $buff);
          } else
            die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
          fclose($in);
          fclose($out);
          @unlink($_FILES['file']['tmp_name']);
        } else
          die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
      } else
        die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
    } else
    {
      // Open temp file
      $out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
      if($out)
      {
        // Read binary input stream and append it to temp file
        $in = fopen("php://input", "rb");

        if($in)
        {
          while($buff = fread($in, 4096))
            fwrite($out, $buff);
        } else
          die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');

        fclose($in);
        fclose($out);
      } else
        die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
    }

    // Return JSON-RPC response
    die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
    return sfView::NONE;
  }

}
