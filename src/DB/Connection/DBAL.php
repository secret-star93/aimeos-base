<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2016-2022
 * @package Base
 * @subpackage DB
 */


namespace Aimeos\Base\DB\Connection;


/**
 * Database connection class for DBAL connections
 *
 * @package Base
 * @subpackage DB
 */
class DBAL extends \Aimeos\Base\DB\Connection\Base implements \Aimeos\Base\DB\Connection\Iface
{
	private $connection;
	private $txnumber = 0;
	private $stmts = [];


	/**
	 * Initializes the DBAL connection object
	 *
	 * @param array $params Associative list of connection parameters
	 * @param string[] $stmts List of SQL statements to execute after connecting
	 */
	public function __construct( array $params, array $stmts )
	{
		parent::__construct( $params );

		$this->stmts = $stmts;
		$this->connect();
	}


	/**
	 * Closes the connection to the database server
	 *
	 * @return \Aimeos\Base\DB\Connection\Iface Connection instance for method chaining
	 */
	public function close() : Iface
	{
		if( $this->inTransaction() ) {
			$this->rollback();
		}

		$this->connection->close();
		return $this;
	}


	/**
	 * Connects (or reconnects) to the database server
	 *
	 * @return \Aimeos\Base\DB\Connection\Iface Connection instance for method chaining
	 */
	public function connect() : \Aimeos\Base\DB\Connection\Iface
	{
		if( $this->connection && $this->connection->ping() ) {
			return $this;
		}

		$param = $this->getParameters();
		$param['driverOptions'][\PDO::ATTR_CASE] = \PDO::CASE_NATURAL;
		$param['driverOptions'][\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
		$param['driverOptions'][\PDO::ATTR_ORACLE_NULLS] = \PDO::NULL_NATURAL;
		$param['driverOptions'][\PDO::ATTR_STRINGIFY_FETCHES] = false;

		$conn = $this->connection;

		$this->connection = \Doctrine\DBAL\DriverManager::getConnection( $param );
		$this->txnumber = 0;

		unset( $conn );

		foreach( $this->stmts as $stmt ) {
			$this->create( $stmt )->execute()->finish();
		}

		return $this;
	}


	/**
	 * Creates a DBAL database statement
	 *
	 * @param string $sql SQL statement, maybe with place holders
	 * @return \Aimeos\Base\DB\Statement\Iface DBAL statement object
	 * @throws \Aimeos\Base\DB\Exception if type is invalid or the DBAL object throws an exception
	 */
	public function create( string $sql ) : \Aimeos\Base\DB\Statement\Iface
	{
		try
		{
			if( strpos( $sql, '?' ) === false ) {
				return new \Aimeos\Base\DB\Statement\DBAL\Simple( $this, $sql );
			}

			return new \Aimeos\Base\DB\Statement\DBAL\Prepared( $this, $sql );
		}
		catch( \Exception $e )
		{
			throw new \Aimeos\Base\DB\Exception( $e->getMessage(), $e->getCode() );
		}
	}


	/**
	 * Returns the underlying connection object
	 *
	 * @return \Doctrine\DBAL\Connection Underlying connection object
	 */
	public function getRawObject()
	{
		return $this->connection;
	}


	/**
	 * Checks if a transaction is currently running
	 *
	 * @return bool True if transaction is currently running, false if not
	 */
	public function inTransaction() : bool
	{
		$conn = $this->connection->getWrappedConnection();

		if( $conn instanceof \PDO ) {
			return $conn->inTransaction();
		}

		return $conn->getNativeConnection()->inTransaction();
	}


	/**
	 * Starts a transaction for this connection.
	 *
	 * Transactions can't be nested and a new transaction can only be started
	 * if the previous transaction was committed or rolled back before.
	 *
	 * @return \Aimeos\Base\DB\Connection\Iface Connection instance for method chaining
	 */
	public function begin() : Iface
	{
		if( $this->txnumber === 0 )
		{
			if( $this->connection->getWrappedConnection()->beginTransaction() === false ) {
				throw new \Aimeos\Base\DB\Exception( 'Unable to start new transaction' );
			}
		}

		$this->txnumber++;
		return $this;
	}


	/**
	 * Commits the changes done inside of the transaction to the storage.
	 *
	 * @return \Aimeos\Base\DB\Connection\Iface Connection instance for method chaining
	 */
	public function commit() : Iface
	{
		if( $this->txnumber === 1 )
		{
			if( $this->connection->getWrappedConnection()->commit() === false ) {
				throw new \Aimeos\Base\DB\Exception( 'Failed to commit transaction' );
			}
		}

		$this->txnumber--;
		return $this;
	}


	/**
	 * Discards the changes done inside of the transaction.
	 *
	 * @return \Aimeos\Base\DB\Connection\Iface Connection instance for method chaining
	 */
	public function rollback() : Iface
	{
		if( $this->txnumber === 1 )
		{
			if( $this->connection->getWrappedConnection()->rollBack() === false ) {
				throw new \Aimeos\Base\DB\Exception( 'Failed to roll back transaction' );
			}
		}

		$this->txnumber--;
		return $this;
	}
}
