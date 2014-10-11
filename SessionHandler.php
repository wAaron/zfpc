<?php
	class SessionHandler
	{
		private $_db;

		private $_table = "sessions";

		private $_maxLifeTime;

		public function __construct()
		{
			$this->_db = new PDO( "mysql:host=sp1.crywc32ujoah.us-east-1.rds.amazonaws.com;dbname=itspurt_session", "itspurt_shopify", "shopify" );
			$this->_maxLifeTime = ini_get( 'session.gc_maxlifetime' );
			session_set_save_handler(
				array ( $this, 'open' ),
				array ( $this, 'close' ),
				array ( $this, 'read' ),
				array ( $this, 'write' ),
				array ( $this, 'destroy' ),
				array ( $this, 'gc' )
			);
			register_shutdown_function( 'session_write_close' );
		}

		public function open() {
			return true;
		}

		public function close() {
			return true;
		}

		public function read( $id )
		{
			$sql = $this->_db->prepare( "SELECT `data` FROM `{$this->_table}` WHERE `id` = ?" );
			$sql->bindParam( 1, $id );
			$sql->execute();
			$data = $sql->fetchColumn();
			return $data;
		}

		public function write( $id, $data )
		{
			$sql = $this->_db->prepare( "REPLACE INTO `{$this->_table}` ( `id`, `data` ) VALUES ( ?, ? )" );
			$sql->bindParam( 1, $id );
			$sql->bindParam( 2, $data );
			return $sql->execute();
		}

		public function destroy( $id )
		{
			$sql = $this->_db->prepare( "DELETE FROM `{$this->_table}` WHERE `id` = ?" );
			$sql->bindParam( 1, $id );
			return $sql->execute();
		}

		public function gc( $maxlifetime )
		{
			$sql = $this->_db->prepare(
				"DELETE FROM `{$this->_table}` WHERE `last_access` < DATE_SUB(NOW(), INTERVAL {$maxlifetime} SECOND)"
			);
			return $sql->execute();
		}
	}

	new SessionHandler();
