<?php

namespace Build\Environment;


class Database implements DatabaseInterface {

  protected $connection;

  protected $version;

  protected $dbtype;

  protected $url;

  protected $username;

  protected $configuration_file;

  protected $port;

  protected $password;

  protected $host;

  /**
   * @inheritDoc
   */
  public function getConnection() {
    return $this->connection;
  }

  /**
   * @inheritDoc
   */
  public function setConnection($connection) {
    $this->connection = $connection;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getVersion() {
    return $this->version;
  }

  /**
   * @inheritDoc
   */
  public function setVersion($version) {
    $this->version = $version;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getDbtype() {
    return $this->dbtype;
  }

  /**
   * @inheritDoc
   */
  public function setDbtype($dbtype) {
    $this->dbtype = $dbtype;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * @inheritDoc
   */
  public function setUrl($url) {
    $this->url = $url;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * @inheritDoc
   */
  public function setUsername($username) {
    $this->username = $username;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getConfigurationFile() {
    return $this->configuration_file;
  }

  /**
   * @inheritDoc
   */
  public function setConfigurationFile($configuration_file) {
    $this->configuration_file = $configuration_file;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getPort() {
    return $this->port;
  }

  /**
   * @inheritDoc
   */
  public function setPort($port) {
    $this->port = $port;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getPassword() {
    return $this->password;
  }

  /**
   * @inheritDoc
   */
  public function setPassword($password) {
    $this->password = $password;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getHost() {
    return $this->host;
  }

  /**
   * @inheritDoc
   */
  public function setHost($host) {
    $this->host = $host;
    return $this;
  }

}
