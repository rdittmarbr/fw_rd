<?PHP
/*-------------------------------------------------------------------------------
Mantenedor     : Rodrigo Dittmar
Linguaguem     : php 7.x
Dependencias   : Class(TcDebug)
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
Modulo         : Coneccao ao Banco de dados / Manutencao de Dados
------------------------------------------------------------------------------*/

/*------------------------------------------------------------------------------
Mantenedor     : Rodrigo Dittmar
Classe         : TcDB
Descricao      : Coneccao com o Banco de Dados / Execucao de Query
--------------------------------------------------------------------------------
Versao 1.0.0.0 : 20051201 - Liberacao do Codigo
Versao 1.0.1.0 : 20060401 - Otimizacao do Codigo
Versao 1.1.0.0 : 20060501 - Otimizacao do Codigo, Inclusao de SQL
Versao 1.2.0.0 : 20061001 - Otimizacao do Codigo
Versao 1.2.0.1 : 20061215 - Correcao Retorno Campos Blob Firebird
Versao 1.3.0.0 : 20070331 - Otimizacao do Codigo/Codificacao de Erros/Desmembramento Manutencao Dados
Versao 1.3.0.5 : 20070630 - Correcao Codificacao Erros/Definicao do Log Debug em Niveis
                            Niveis de Debug
                                0 - Construtores / Destrutores
                                1 - Conexao ao DB
                                2 - Recuperacao de Dados do DB
                                7 - Erros
                                9 - Auxiliares / Privadas / Sem muita importancia
Versao 1.4.0.0 : 20120601 - Otimizacao do Codigo
Versao 1.0.1.0 : 2019     - Adaptação para o projeto CLibras
------------------------------------------------------------------------------*/
Class TcDB {

  private $FConfig    = array();       //Dados de Coneccao ao Servidor
  public  $FCon       = false;         //Recurso para a coneccao ao DB
  private $FConnected = false;         //Conectado ao DB (true/false)
  public  $FError     = array();       //Armazena os Erros na Execucao do SQL (Funcao / Codigo / Mensagem / Historico)

  private $FOwner = '';                //Proprietario
  private $FDebug;                     //recurso para gravar log do debug
  private $FWrite = array();           //Niveis de gravacao do debug

  /*----------------------------------------------------------------------------
  __construct(0)      : Cria a classe e atualiza os atributos
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aOwner              :string     : Proprietario do Objeto
  aConfig             :array      : Array de Configuração (modelo $esDB)
  a              :&tcDebug   : Objeto Debug
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  ----------------------------------------------------------------------------*/
  function __construct( $aOwner = '' , $aConfig = false , &$aDebug = false ) {

    //Definindo as propriedades do objeto
    $this -> FOwner = $aOwner;
    $this -> FDebug = $aDebug;
    $this -> FWrite = array(0,0,0,0,0,0,0,0,0,0);

    //Setando os parametros da classe -- Apenas o que for necessário alterar
    if( is_array($aConfig) ) {
      foreach ($aConfig as $a=>$b) {
        $this -> FConfig[strtolower($a)] = $b;
      };
    }

    //Definindo os Niveis de Backup
    if( is_object($this -> FDebug) ) {
      $this -> FWrite = $this -> FDebug -> FWrite;
      $this -> logDebug( __METHOD__ , 'Debug Level :' . implode($this -> FWrite,",") );
    }
  }
  /*----------------------------------------------------------------------------
  __destruct(0)       : Libera a memoria alocada e encerra a coneccao
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  ----------------------------------------------------------------------------*/
  function __destruct() {

    //Fechando a Coneccao ao DB
    if( $this -> FConnected )
      $this -> close();

    if( $this -> FWrite[0] ) $this -> logDebug( __METHOD__ );
  }
  /*----------------------------------------------------------------------------
  logDebug            : Armazena o Log de Debug
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aFunction           :string     : Evento da Classe
  aDes                :string     : Descricao do Evento
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  ----------------------------------------------------------------------------*/
  function logDebug( $aFunction , $aDes = '' ) {
    $this -> FDebug -> write( $this -> FOwner, $aFunction , $aDes );
  }
  /*------------------=-----------=---------------------------------------------------------------------------=
  status              : Retorna o satus da classe
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  -----------------------------------------------------------------------------------------------------------*/
  function status(  ) {

    $aDB = array();
    $aDB[0] = "ES_DBFILE";
    $aDB[1] = "ES_DBMYSQL40";
    $aDB[2] = "ES_DBMYSQL41";
    $aDB[3] = "ES_DBFIREBIRD";

    $a ="<div><Table><tr><th>Item</th><th>Valor</th></tr>";
    //configurações do banco de dados
    foreach($this -> FConfig as $b=>$c) {
      $a=$a . "<tr><td>$b</td><td>" . ($b=="pass"?"******":($b=="type"?$aDB[$c]:$c)) . "</td>";
    }

    //Status da conecção
    $this -> connect();
    $a=$a . "<tr><td>Connected</td><td>" . ($this -> FConnected?"OK":"Not Connected") . "</td>";

    $a=$a . "</tr></table></div>";
    return $a;
  }
  /*============================================================================
  Coneccao ao Banco de Dados
  /*----------------------------------------------------------------------------
  connect(1)          : Conecta e Seleciona o banco de dados - Coneccao Permanente
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  ----------------------------------------------------------------------------*/
  function connect() {

    //Conectando ao DB se não conectado
    if( !($this -> FConnected) ) {

      if( $this -> FWrite[1] )
            $this -> logDebug( __METHOD__ , $this -> FConfig['user'] .'@'. $this -> FConfig['host'] .':'. $this -> FConfig['db'] );

      if( $this -> FConfig['type'] == ES_DBMYSQL40 ) {
        $this -> FCon = @mysql_connect( $this -> FConfig['host'] , $this -> FConfig['user'] , $this -> FConfig['pass'] );
        if ( ($this -> FCon) and ( !@mysql_select_db($this -> FConfig['db'] , $this -> FCon) ) ) {
          mysql_close($this->FCon);
          $this -> FCon = false;
        }
      } else if( $this -> FConfig['type'] == ES_DBMYSQL41 ) {
        $this -> FCon = @mysqli_connect( $this -> FConfig['host'] , $this -> FConfig['user'] , $this -> FConfig['pass'] , $this -> FConfig['db'] );
      } else if( $this -> FConfig['type'] == ES_DBFIREBIRD ) {
        $this -> FCon = @ibase_connect( $this -> FConfig['host'] .":". $this -> FConfig['db'] , $this -> FConfig['user'] , $this -> FConfig['pass'] , 'ISO8859_1' );
      }
      $this -> error( __METHOD__ );
      $this -> FConnected = ($this -> FCon?true:false);
    }

    return( $this -> FConnected );
  }
  /*----------------------------------------------------------------------------
  close(1)            : Encerra a coneccao com o Servidor
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
                      :boolean    : Conectado ou nao ( True / False )
  ----------------------------------------------------------------------------*/
  function close() {

    if( $this -> FConnected ) {
      if( $this -> FWrite[1] ) $this -> logDebug( __METHOD__ );

      switch ($this -> FConfig['type']) {
        case ES_DBMYSQL40  : mysql_close( $this -> FCon ); break;
        case ES_DBMYSQL41  : mysqli_close( $this -> FCon ); break;
        case ES_DBFIREBIRD : ibase_close( $this -> FCon ); break;
      }
      $this -> error( __METHOD__ );
    }

    $this -> FCon = false;
    $this -> FConnected = false;
  }
  /*----------------------------------------------------------------------------
  connected(9)        : Retorna se houve coneccao ao DB
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
                      :boolean     : Conectado ou nao ( True / False )
  ----------------------------------------------------------------------------*/
  function connected() {
    if( $this -> FWrite[9] ) $this -> logDebug( __METHOD__ , ( $this -> FConnected ) );
    return( $this -> FConnected );
  }
  /*----------------------------------------------------------------------------
  get_Host_Type(9)    : Retorna o tipo de servidor conectado
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
                      :integer     : Tipo de coneccao
  ----------------------------------------------------------------------------*/
  function get_Host_Type() {
    if( $this -> FWrite[9] ) {
      $a = array ("ES_DBFILE" ,"ES_DBMYSQL40","ES_DBMYSQL41" ,"ES_DBFIREBIRD" );
      $this -> logDebug( __METHOD__ , $a[$this -> FConfig['type']] );

    }
    return( $this -> FConfig['type'] );
  }
  /*============================================================================
  Manutencao dos Recursos
  /*----------------------------------------------------------------------------
  query(2)            : Executa uma query
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aQuery              :string      : Query SQL
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
                      :recurso     : Recurso de retorno dos Dados
  ----------------------------------------------------------------------------*/
  function query( $aQuery = '') {

    $result = false;

    if( $this -> FWrite[2] )
      $this -> logDebug( __METHOD__ , $aQuery );

    //Verifica se ha coneccao e inicia se nao houver.
    if( !$this -> FConnected ) $this -> connect();

    switch( $this -> FConfig['type'] ) {
      case ES_DBMYSQL40  : $result = @mysql_query( $aQuery , $this -> FCon ); break;
      case ES_DBMYSQL41  : $result = $this -> FCon -> query( $aQuery ); break;
      case ES_DBFIREBIRD : $result = @ibase_query( $this -> FCon , str_replace( "\'", "''" , $aQuery ) ); break;
    }

    return( $result );
  }
  /*---------------------------------------------------------------------------------------------------------
  error(2)            : Recupera o Codigo e Mensagem de Erro do Banco de Dados e armazena no vetor
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aFunction           :string      : Nome da Funcao de Erro
  aHistory            :string      : Codigo/Opcao do Erro
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  ---------------------------------------------------------------------------------------------------------*/
  function error( $aFunction ,  $aHistory = '' ) {

    $aError = array(0,"",0,"","");
    if( $this -> connected() ) {
      switch( $this -> FConfig['type'] ) {
        case ES_DBMYSQL40  : $aError = array( microtime(true), $aFunction , @mysql_errno($this -> FCon) , @mysql_error($this -> FCon) , $aHistory ); break;
        case ES_DBMYSQL41  : $aError = array( microtime(true), $aFunction , @mysqli_errno($this -> FCon) , @mysqli_error($this -> FCon) , $aHistory ); break;
        case ES_DBFIREBIRD : $aError = array( microtime(true), $aFunction , ibase_errcode() , ibase_errmsg() , $aHistory ); break;
      }
    } else  {
      switch( $this -> FConfig['type'] ) {
        case ES_DBMYSQL40  : $aError = array( microtime(true), $aFunction , @mysql_errno($this -> FCon) , @mysql_error($this -> FCon) , $aHistory ); break;
        case ES_DBMYSQL41  : $aError = array( microtime(true), $aFunction , @mysqli_connect_errno() , @mysqli_connect_error() , $aHistory ); break;
        case ES_DBFIREBIRD : $aError = array( microtime(true), $aFunction , ibase_errcode() , ibase_errmsg() , $aHistory ); break;
      }
    }

    if( $aError[1] <> 0 ) {
      $this -> FError[] = $aError;
      if( $this -> FWrite[2] ) $this -> logDebug( __METHOD__ , 'ERROR: ' .  explode( $this -> FError[count($this -> FError) -1],"," ) );
      return( true );
    }
  }
}




/*-------------------------------------------------------------------------------------------------------------
Mantenedor     : Rodrigo Dittmar
Classe         : TcQuery_light
Descricao      : Execucao e Manutencao dados Query
---------------------------------------------------------------------------------------------------------------
Versao 1.0.0.0 : 20070331 - Liberacao do Codigo
Versao 1.0.0.5 : 20070630 - Correcao Codificacao Erros/Definicao do Log Debug em Niveis
                            Niveis de Debug
                                0 - Construtores / Destrutores
                                1 - Conexao ao DB
                                2 - Recuperacao de Dados do DB / Query
                                5 - Recuperacao de Dados do DB / fetch_row / get_col
                                7 - Erros
                                9 - Auxiliares / Privadas / Sem muita importancia
Versao 1.4.0.0 : 20120601 - Otimizacao do Codigo
Versao 1.5.0.0 : 2019     -
-------------------------------------------------------------------------------------------------------------*/
Class TcQuery_light {

  private $FHostCon;                      //Classe TcDB p/Coneccao ao DB
  private $FConfigType;                //Tipo de banco de dados
  private $FResult;                    //Objeto resultado da execucao da query

  private $FSQL = '';                  // Código SQL
  private $FSQL_Param = array();       // Parametros do SQL

  private $FOwner = '';                //Proprietario
	private $FDebug = false;             //recurso para gravar log do debug
  private $FLog = array();             //Niveis de gravacao do log/debug

	/*------------------=-----------=----------------------------------------------------------------------------
	__construct(0)      : Cria a classe e atualiza os atributos
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	aOwner              :string     : Proprietario do Objeto
	aTcDB               :&TcDB      : Objeto Banco de Dados
	aTcDebug            :&tcDebug   : Objeto Debug
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	-----------------------------------------------------------------------------------------------------------*/
	function __construct( $aOwner = '' , &$aTcDB , &$aTcDebug = false ) {

    $this -> FOwner    = $aOwner;                  // Proprietario
		$this -> FHostCon  = $aTcDB;                   // Objeto TcDB
    $this -> FDebug    = $aTcDebug;                // Objeto TcDebug
    $this -> FLog    = array(0,0,0,0,0,0,0,0,0,0); //Nivel do Log/Debug

    // Recuperando o tipo de Banco de Dados
    $this -> FConfigType = $this -> FHostCon -> get_Host_Type();
    $this -> FResult     = false;

		//Definindo os Niveis de Backup
		if( is_object($this -> FDebug) ) {
			$this -> FLog = $this -> FDebug -> FWrite;
			$this -> log( __METHOD__ , 'Debug Level :' . implode($this -> FLog,",") );
		}

  }
	/*------------------=-----------=----------------------------------------------------------------------------
  __destruct(0)       : Libera a memoria alocada e encerra a conexao
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  -----------------------------------------------------------------------------------------------------------*/
  function __destruct() {
    //Limpando o Resultado
    $this -> freeResult();

    if( $this -> FLog[0] ) $this -> log( __METHOD__ );
  }
	/*------------------=-----------=----------------------------------------------------------------------------
	log                 : Armazena o Log de Debug
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	aClass              :string     : Classe
	aFunction           :string     : Evento da Classe
	aDes                :string     : Descricao do Evento
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	-----------------------------------------------------------------------------------------------------------*/
	function log( $aFunction , $aDes = '' ) {
		$this -> FDebug -> log( $this -> FOwner, $aFunction , $aDes );
	}
  /*===========================================================================================================
  Manutencao dos Recursos e Conexao ao DB
	/*------------------=-----------=----------------------------------------------------------------------------
  connect(5)          : Conecta ao DB retornando true/false
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
                      :boolean    : Sucesso/Fracasso
  -----------------------------------------------------------------------------------------------------------*/
  function connect() {
    if( $this -> FLog[5] ) $this -> log( __METHOD__ );

    return( $this -> FHostCon -> connect() );
  }
	/*------------------=-----------=----------------------------------------------------------------------------
  freeResult(5)       : Limpa o resultado gerado pela query
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
                      :boolean              : Sucesso ou Fracasso ( True / False )
  -----------------------------------------------------------------------------------------------------------*/
  function freeResult() {

    if( $this -> FLog[5] ) $this -> log( __METHOD__ );

    if( is_resource($this -> FResult) or is_object($this -> FResult) ) {
      switch( $this -> FConfigType ) {
        case ES_DBMYSQL40  : @mysql_free_result( $this -> FResult ); break;
        case ES_DBMYSQL41  : @mysqli_free_result( $this -> FResult ); break;
        case ES_DBFIREBIRD : @ibase_free_result( $this -> FResult ); break;
      }
    }

    return( $this -> error( __METHOD__ ) );
  }
	/*------------------=-----------=----------------------------------------------------------------------------
  query(2)            : Executa uma query diretamente
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aQuery              :string     : Query SQL
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
                      :boolean    : Sucesso/Fracasso
  -----------------------------------------------------------------------------------------------------------*/
  function query( $aQuery ) {

    if( $this -> FLog[2] ) $this -> log( __METHOD__ , $aQuery );

    //Verifica se ha coneccao e inicia se nao houver.
    if ( $this -> connect() ) {
      switch( $this -> FConfigType ) {
        case ES_DBMYSQL40  : $this -> FResult = @mysql_query( $aQuery , $this -> FHostCon -> FCon ); break;
        case ES_DBMYSQL41  : $this -> FResult = $this -> FHostCon -> FCon -> query( $aQuery ); break;
        case ES_DBFIREBIRD : $this -> FResult = @ibase_query( $this -> FHostCon -> FCon , str_replace( "\'", "''" , $aQuery ) ); break;
      }
    } else
      if( $this -> FWrite[2] ) $this -> FDebug -> log(  $this -> FOwner , __METHOD__ , 'Error in conection!' );

    return( $this -> error( __METHOD__ , $aQuery ) );
  }
	/*------------------=-----------=----------------------------------------------------------------------------
  fetchRow(5)         : Retorna linha a linha o resultado da query
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aType               :boolean    : Tipo de Retorno (True -> Indice / False -> Coluna)
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
                      :array      : Array de dados
  -----------------------------------------------------------------------------------------------------------*/
  function fetchRow( $aType = False ) {

    $aReturn = false;

    //Recurso do DB
    if( is_resource($this -> FResult) or is_object($this -> FResult) ) {
      switch( $this -> FConfigType ) {
        case ES_DBMYSQL40  : $aReturn = ($aType ? mysql_fetch_row( $this -> FResult ) : mysql_fetch_assoc( $this -> FResult ) ); break;
        case ES_DBMYSQL41  : $aReturn = ($aType ? mysqli_fetch_row( $this -> FResult ) : mysqli_fetch_assoc( $this -> FResult ) ); break;
        case ES_DBFIREBIRD : $aReturn = ($aType ? ibase_fetch_row( $this -> FResult , IBASE_TEXT ) : ibase_fetch_assoc( $this -> FResult , IBASE_TEXT ) );
      }
      if ( !$aType and is_array($aReturn) )
        $aReturn = array_change_key_case( $aReturn , CASE_LOWER  );
    }

    if( $this -> FLog[5] ) $this -> log( __METHOD__ , $aReturn );

    return( $aReturn );
  }
	/*------------------=-----------=----------------------------------------------------------------------------
  getCol(5 )          : Retorna as Colunas,tipo do resultado de uma query
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aReturn             : Array das Colunas de retorno
  -----------------------------------------------------------------------------------------------------------*/
  function getCol( )  {

    $aReturn   = array();     //Armazena as Colunas
    $aFields   = 0;           //Numero de Colunas (firebird)
    $aHostType = $this -> FConfigType;

    if( is_resource($this -> FResult) or is_object($this -> FResult) )
      if ( $aHostType == ES_DBMYSQL40 ) {
        while( $ColInfo = mysql_fetch_field( $this -> FResult ) )
          $aReturn[$ColInfo->name] = array( $this->dataType( $ColInfo->type ) );
      } elseif ( $aHostType == ES_DBMYSQL41 ) {
        while( $ColInfo = mysqli_fetch_field( $this -> FResult ) )
          $aReturn[$ColInfo->name] = array( $this->dataType( $ColInfo->type ) );
      } elseif ( $aHostType == ES_DBFIREBIRD ) {
        $aFields = ibase_num_fields( $this -> FResult );
        for( $i=0 ; $i < $aFields ; $i++ ) {
          $ColInfo = ibase_field_info( $this -> FResult , $i );
          $aReturn[$ColInfo['alias']] = array( $this->dataType( $ColInfo['type'] ) );
        }
      }
      if ( is_array($aReturn) )
        $aReturn = array_change_key_case( $aReturn , CASE_LOWER  );

    if( $this -> FLog[5] ) $this -> log( __METHOD__ , $aReturn );

    return( $aReturn );
  }
  /*===========================================================================================================
  Manutencao dos Erros
	/*------------------=-----------=----------------------------------------------------------------------------
  error(2)            : Adiciona um erro a classe TcDB - se houver
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aFunction           :string               : Nome da Funcao de Erro
  aHistory            :string               : Codigo/Opcao do Erro
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
                      :boolean              : Houve ou nao Erro (True / False)
  -----------------------------------------------------------------------------------------------------------*/
  function error( $aFunction , $aHistory = '' ) {

    if( $this -> FLog[2] ) $this -> log( __METHOD__ );

    return( $this -> FHostCon -> error( $aFunction , $aHistory ) );
  }
	/*------------------=-----------=----------------------------------------------------------------------------
  getError(2)         : Recupera o ultimo erro
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
                      :Array                : Ultimo erro
  -----------------------------------------------------------------------------------------------------------*/
  function getError( ) {

    if( $this -> FLog[2] ) $this -> log( __METHOD__ , $this -> FHostCon -> FError[ count($this -> FHostCon -> FError) -1 ] );

    //Busca no TcDB o erro e se houver armazena-o
    return( $this -> FHostCon -> FError[ count($this -> FHostCon -> FError) -1 ] );
  }
  /*===========================================================================================================
  Funcoes privadas do sistema
  /*------------------=-----------=----------------------------------------------------------------------------
  data_Type(9)        : Converte o Tipo de Dados para a Definicao do sistema
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aType               :string     : Tipo de dados
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  ES_VB*              :DEFINITION : Definicao para o tipo de dados resultante
  -----------------------------------------------------------------------------------------------------------*/
  private function data_Type( $aType ) {

    $aType = strtoupper($aType);

    if( $this -> FLog[9] ) $this -> log( __METHOD__ , $aType );

    if ( ( $aType == 'INTEGER' ) or ( $aType == 'INT' ) )
      return ES_VBINTEGER;
    else if ($aType == 'DOUBLE')
      return ES_VBDOUBLE;
    else if ( ( $aType == 'VARCHAR' ) or ( $aType == 'CHAR' ) or ( $aType == 'STRING' ) )
      return ES_VBVARCHAR;
    else if ($aType == 'DATE')
      return ES_VBDATE;
    else if ($aType == 'TIME')
      return ES_VBTIME;
    else if ($aType == 'BLOB')
      return ES_VBBLOB;
    else
      return ES_VBUNKNOW;
  }
}
