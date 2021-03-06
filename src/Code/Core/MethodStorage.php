<?php

namespace Oploshka\Rpc;

class MethodStorage implements \Oploshka\RpcInterface\MethodStorage {
  
  private $methods = [];
  
  /**
   * 
   * @param string $methodName
   * @param string $methodClass
   * // TODO: @param string $methodGroup
   * @throws \Exception
   */
  public function add($methodName, $methodClass, $methodGroup = 'default'){
    
    // Название метода должно быть строкой
    if( !is_string($methodName) ){
      throw new \Exception('RPCMethodStorage: Method name no string');
    }
    if( !is_string($methodClass) ){
      throw new \Exception('RPCMethodStorage: Method class no string');
    }
     if( !is_string($methodGroup) ){
       throw new \Exception('RPCMethodStorage: Method group no string');
     }
    
    // Запрет переопределения существующего метода
    if( isset($this->methods[$methodName]) ){
      throw new \Exception('RPCMethodStorage: Add dublicate method >>' . $methodName . '<<');
    }
    
    // если все хорошо то добавляем
    $this->methods[$methodName] = [
      'name'  => $methodName,
      'class' => $methodClass,
      'group' => $methodGroup,
    ];
  }
  
  /**
   * @return array || false
   */
  public function getMethodInfo($methodName) {
    if( !isset($this->methods[$methodName]) ){
      return false;
    }
    return $this->methods[$methodName];
  }
}
