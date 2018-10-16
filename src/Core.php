<?php

namespace Oploshka\Rpc;

use PHPUnit\Runner\Exception;

class Core {
  
  private $MethodStorage;
  private $Reform;
  private $LoadData;
  private $Response;

  private $headerSettings = [
    'Access-Control-Allow-Origin' => '*',
  ];
  private $phpSettings = [
    'error_reporting'         => E_ALL,
    'display_errors'          => 1,
    'display_startup_errors'  => 1,
    'date.timezone'           => 'UTC',
  ];
  
  public function __construct($MethodStorage, $Reform) {
    $this->MethodStorage  = $MethodStorage;
    $this->Reform         = $Reform;
    // TODO fix
    $this->DataLoader     = new DataLoader();
    $this->Response       = new Response();
    // $this->Error          = new Error();
  }
  
  /**
   * @param array $settings
   */
  public function setHeaderSettings($settings) {
    $this->headerSettings = $settings;
  }
  public function getHeaderSettings() {
    return $this->headerSettings;
  }
  
  /**
   * @param array $settings
   */
  public function setPhpSettings($settings) {
    $this->phpSettings = $settings;
  }
  public function getPhpSettings() {
    return $this->headerSettings;
  }
  
  
  public function autoRun() {
    $methodName = '';
    $methodData = [];
    $errorCode = $this->DataLoader->load($methodName, $methodData);
    if($errorCode !== false){
      $this->Response->setError($errorCode);
      return $this->Response;
    }
    $this->run($methodName, $methodData);
  }
  
  
  /**
   * Run Rpc method
   *
   * @param string $methodName string
   * @param array $methodData array
   *
   * @return Response
   *
   */
  public function run($methodName, $methodData) {
  
    ob_start();
    if ($this->headerSettings !== [] && headers_sent()) {
      $this->Response->setError('ERROR_SET_HEADER');
      return $this->Response;
    }
    
    foreach ($this->headerSettings as $k => $v){
      header("{$k}: {$v}");
    }
  
    try{
      foreach ($this->phpSettings as $k => $v){
        ini_set($k, $v);
      }
    } catch (Exception $e){
      $this->Response->setLog( 'iniSetError', $e->getMessage() );
      $this->Response->setError('ERROR_INI_SET');
      return $this->Response;
    }
    
    try{
      $response = $this->runMethod($methodName, $methodData);
    } catch (Exception $e){
      $this->Response->setLog( 'runMethodError', $e->getMessage() );
      $this->Response->setError('ERROR_METHOD_RUN');
      return $this->Response;
    }

    $this->Response->setLog('echo', ob_get_contents() );
    ob_end_clean();
    
    return $response;
  }
  
  private function runMethod($methodName, $methodData){

    // validate method name
    if( !is_string($methodName) || $methodName == '') {
      $this->Response->setError('ERROR_NO_METHOD_NAME');
      return $this->Response;
    }
  
    // get method info
    $methodInfo = $this->MethodStorage->getMethodInfo($methodName);
    if(!$methodInfo) {
      $this->Response->setError('ERROR_NO_METHOD_INFO');
      return $this->Response;
    }
  
    // method class create
    $MethodClassName = $methodInfo['class'];
    $MethodClass = new $MethodClassName();
  
    // validate class interface
    if ( !($MethodClass instanceof \Oploshka\Rpc\Method) ) {
      $this->Response->setError('ERROR_NOT_INSTANCEOF_INTERFACE', false);
      return $this->Response;
    }
  
    // validate method data
    $data = $this->Reform->item($methodData, ['type' => 'array', 'validate' => $MethodClass->validate()] );
    if($data === NULL) {
      $this->Response->setError('ERROR_NOT_VALIDATE_DATA');
      return $this->Response;
    }

    $responseLink = $this->Response;
    try {
      $MethodClass->run($this->Response, $data);
    } catch (\Exception $e) {
      $this->Response->setLog('methodRun', $e->getMessage());
    }
  
    // $this->Response is Response class?
    $responseType = gettype ( $this->Response );
    if( $responseType === 'object' && get_class ( $this->Response ) != 'Oploshka\Rpc\Response'){
      
      $responseLink->setLog( 'responseErrorType', gettype($this->Response) );
      if( gettype ( $this->Response ) == 'object' ) {
        $this->Response->setLog( 'responseErrorClass', get_class($this->Response) );
      }
      
      $responseLink->setError('ERROR_NOT_CORRECT_METHOD_RETURN');
      // reset response
      $this->Response = $responseLink;
    }
    
    return $this->Response;
  }
  
}