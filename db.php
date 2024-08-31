<?php

class Db { 
  static private $instance = null; 
  static private $connection = null;
  protected $transactionDepth = 0; 
 
  private function __construct() { 
  }
  
  private function _connect() { 
     if (self::$connection === null) {
         try {
            /* Código para conectarse a la BD */
             self::$connection = new PDO('mysql:host=localhost', 'root', 'root');
         } catch (PDOException $e) {
            echo "error pdo: ";
            echo $e->getMessage();
         }
      }

      return self::$connection;
   }
   
   /* Nada que clonar en el patrón de diseño Singleton */
   private function __clone()
   {
     
   }

   static public function getInstance()
   {
      if (is_null(self::$instance)) {
         self::$instance = new self();
      }

      return self::$instance;
   }

   static public function closeConnection()
   {
      self::$instance = null;

      if (isset(self::$connection)) {
         self::$connection = null;
      }
   }

   public function getConnection()
   {
      return $this->_connect();
   }

   protected function prepare($query, $params = array())
   {
      $stmt = $this->getConnection()->prepare($query);
      if (is_array($params)) {
         foreach ($params as $param => $value) {
            if (is_bool($value)) {
               $type = PDO::PARAM_BOOL;
            } elseif ($value === null) {
               $type = PDO::PARAM_NULL;
            } elseif (is_integer($value)) {
               $type = PDO::PARAM_INT;
            } else {
               $type = PDO::PARAM_STR;
            }
            $stmt->bindValue(":$param", $value, $type);
         }
      }

      return $stmt;
   }

   public function execute($sql, $params = array())
   {
      $stmt = $this->prepare($sql, $params);
      $stmt->execute();
      $stmt->closeCursor();

      return $stmt->rowCount();
   }

   public function begin()
   {
      if ($this->transactionDepth == 0) {
         $this->getConnection()->beginTransaction();
      }else{
         $this->execute("SAVEPOINT LEVEL{$this->transactionDepth}");
      }
      $this->transactionDepth++;
   }

   public function commit()
   {
      $this->transactionDepth--;
      if ($this->transactionDepth == 0) {
         return $this->getConnection()->commit();
      }else{
         return $this->execute("RELEASE SAVEPOINT LEVEL{$this->transactionDepth}");
      }
   }

   public function rollback()
   {
      if ($this->transactionDepth == 0) {
         throw new LogicException("Ninguna transacción en curso para retroceder");
      }
      $this->transactionDepth--;
      if ($this->transactionDepth == 0) {
         return $this->getConnection()->rollback();
      }else{
         return $this->execute("ROLLBACK TO SAVEPOINT LEVEL{$this->transactionDepth}");
      }
   }
}
