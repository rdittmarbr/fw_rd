<?PHP
/*-------------------------------------------------------------------------------------------------------------
Mantenedor     : Rodrigo Dittmar
Linguaguem     : php 7.x
Dependencias   : _TcDebug; _TcDB; _TcUser;
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
Modulo         : Processamento de requisições
-------------------------------------------------------------------------------------------------------------*/

/*-------------------------------------------------------------------------------------------------------------
Classe         : TcPackage
Descricao      : Processamento de requisições
---------------------------------------------------------------------------------------------------------------
Versao 1.0.0.0 : 20070731 - Liberacao da versao
													- Debug em n�veis - Nivel de gravacao do debug 0 a 10
Versão 1.5     : 2019     - Adaptação ao projeto CLibras UFPR
-------------------------------------------------------------------------------------------------------------*/
Class TcPackage extends stdClass {

	private $FHostCon;                   //recurso para a coneccao ao DB
  private $FQuery;                     //recurso para execucao da query

	public  $FVersion  = '0.5';          //Versao do Pacote
	public  $FReturn   = array();        //Armazena o retorno da classe
	public  $FLanguage = ES_SYLANGUAGE;  //Idioma

	private $FModule = array();          //Variavel de armazenamento dos modulos carregados

  private $FOwner = '';                //Proprietario
	private $FDebug = false;             //recurso para gravar log do debug
  private $FLog = array();             //Niveis de gravacao do log/debug

	/*------------------=-----------=----------------------------------------------------------------------------
	__construct(0)      : Cria a classe e atualiza os atributos
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	aOwner              :string     : Proprietario do Objeto
	aTcDB               :&TcDB      : Objeto Banco de Dados
	aTcUser             :&TcUser    : Objeto Usuario
	aTcDebug            :&tcDebug   : Objeto Debug
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	-----------------------------------------------------------------------------------------------------------*/
	function __construct( $aOwner = '' , &$aTcDB , &$aTcUser, &$aTcDebug = false ) {

    $this -> FOwner    = $aOwner;                  // Proprietario
		$this -> FHostCon  = $aTcDB;                   // Objeto TcDB
    $this -> FUser     = $aTcUser;                 // Objeto TcUser
    $this -> FDebug    = $aTcDebug;                // Objeto TcDebug
    $this -> FLog    = array(0,0,0,0,0,0,0,0,0,0); //Nivel do Log/Debug

		//Variaveis de Retorno
    $this -> FReturn['v'] = 1;                   //Tipo de Pacote
		$this -> FReturn['pkg'] = array();           //Sub Pacotes
		$this -> FReturn['header'] = ES_headerJSON;  //Header http
		// ES_headerTEXT = header('Content-Type: text/html');
		// ES_headerJSON = header('Content-Type: application/json');
		// ES_headerMPG = header('Content-Type: ');
		// ES_headerJPG = header('Content-Type: mpg');

		//Definindo os Niveis de Backup
		if( is_object($this -> FDebug) ) {
			$this -> FLog = $this -> FDebug -> FWrite;
			$this -> log( __METHOD__ , 'Debug Level :' . implode($this -> FLog,",") );
		}

		//Recuperando os módulos da sessão
		if( isset($_SESSION['TcPackage']) and isset( $_SESSION['user'] ) )
		  $this->FModule = $_SESSION['TcPackage'];

	}
	/*------------------=-----------=----------------------------------------------------------------------------
	__destruct(0)       : Libera a memoria alocada e encerra a conexao
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	-----------------------------------------------------------------------------------------------------------*/
	function __destruct() {
    //Gravando na sessão os módulos carregados
    if( isset( $_SESSION['user'] ) )
			$_SESSION['TcPackage'] = $this->FModule;

		//Destruindo as Variaveis
		unset( $this->FReturn );
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
	Manutencao dos pacotes/módulos
	/*------------------=-----------=----------------------------------------------------------------------------
	writePackage(1)     : Retorna via json os dados contidos no pacote
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	-----------------------------------------------------------------------------------------------------------*/
	function writePackage() {
		if( $this->FLog[1] ) $this -> log( __METHOD__ , 'Header ' . $this -> FReturn['header'] );

		switch ($this -> FReturn['header']) {
			case ES_headerTEXT:
				header('Content-Type: application/json');break;
		  case ES_headerJSON:
				header('Content-Type: application/json');break;
      case ES_headerMPEG:
				header('Content-Type: application/json');break;
			case ES_headerJPG:
				header('Content-Type: application/json');break;
			case ES_headerPDF:
				header('Content-Type: application/pdf');break;
		}
		print_r( json_encode( $this->FReturn ) );
	}
	/*------------------=-----------=----------------------------------------------------------------------------
	addJS(5)            : Adiciona um JS de carácter geral
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	aType               :string     : Ordem de xecução do JS - _B_efore(antes dos modulos)/_A_fter (após os modulos)
	aModule             :string     : Nome do módulo que irá executar a função
	aJS                 :string     : Código JS a executar
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	-----------------------------------------------------------------------------------------------------------*/
	function addJS()  {
		if( func_num_args() == 2 ) {
			$aType = func_get_args(0);
			$aJS   = func_get_args(1);
		} else if ( func_num_args() == 3 ) {
			$aType   = func_get_args(0);
			$aModule = func_get_args(1);
			$aJS     = func_get_args(2);
		}
		if( $this -> FLog[5] ) $this -> log( __METHOD__ , $aType .":". $aJS );

		$aType = substr(strtoupper($aType),1,1);

		$this->FReturn['js'][$aType][] = $aJS;
	}
	/*------------------=-----------=----------------------------------------------------------------------------
	getModule(1)        : Adiciona um módulo ao pacote de dados - verificando a permissão do usuário
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	aForm               :string     : Modulo a Carregar
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	-----------------------------------------------------------------------------------------------------------*/
	function getData( $aForm , &$aModule ) {

		//---------------------------------------------------------------------------------
		// Recuperando os dados do formulário
		if( isset( $aModule['sql'] ) and isset( $aModule['sql']['select']) ) {
			$this -> sqlSelect( $aModule['sql']['select'] , $aModule['sql']['param'] );

			//-------------------------------------------------------------------------------
			//Recuperando as linhas de dados
			while( $aData = $this -> FQuery -> fetchRow() ) {
				//Convertendo para o padrão do form
				foreach( $aData as $aCol => $aValue ) {
					$this -> FModule[$aForm]['data'][ $aModule['data'][$aCol][0] ] = $aValue;
				}

				if( $this -> FLog[1] ) $this -> log( __METHOD__ , $this->FModule[$aForm]['data']  );
			}
		}

		return true;
	}
	/*------------------=-----------=----------------------------------------------------------------------------
	getModule(1)        : Adiciona um módulo ao pacote de dados - verificando a permissão do usuário
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	aForm               :string     : Modulo a Carregar (separado em vírgula para mais módulos)
	aType               :integer    : Tipo de Pacote
	aValue              :array      : Dados do formulario
	aOperation          :integer    : Operacao de Dados (0 consulta / 1 alteracao / 2 inclusao / 3 exclusao)
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	-----------------------------------------------------------------------------------------------------------*/
	function getModule( $aForm , $aType , $aValue = false , $aOperation = 0 ) {

		if( $this -> FLog[1] ) $this -> log( __METHOD__ , Array( $aForm , $aType , $aValue , $aOperation ) );

		//------------------------------------------------
	  // Limpando a memoria para carga do módulo
		$aModule = [];
		$aModule['cfg'] = [];
		$aModule['form']= [];
		$aModule['js']  = [];
		$aModule['js']['before'] = [];
		$aModule['js']['after']  = [];
		$aModule['path'] = ES_MODULE . substr($aForm , 0 , 3 ) . '/' .
		                   $aForm . '.php';                             // Modulo - Arquivo

		//------------------------------------------------
		//Carregando os modulos Formulario
		if( file_exists( $aModule['path'] ) ) {

			// propriedades de segurança padronizadas
			$aModule['cfg']['name']   = '';                  // Módulo é executado publicamente
			$aModule['cfg']['system'] = false;               // Módulo do sistema
			$aModule['cfg']['public'] = false;               // Módulo é executado publicamente
			$aModule['cfg']['registered'] = true;            // Módulo é executado somente para usuários registrados
			$aModule['sql']['param'] = array();              // Parametros do SQL;

			include( $aModule['path'] );
			//Log das informações que estão presentes no módulo
			//if( $this -> FLog[9] ) $this -> log( __METHOD__ , $aModule );

			//---------------------------------------------------------------------------------
			// Verificando os parâmetros de segurança do módulo
			if( ( $aModule['cfg']['public'] == true and ( $aModule['cfg']['registered'] == false or $this -> FUser -> active() )
			    ) or	isset( $_SESSION['modules'][$aForm] ) ) {
				$this -> FModule[$aForm] = [];
				$this -> FModule[$aForm] = $aModule;
    		$this -> FModule[$aForm]['main'] = $aModule['main'];
				$this -> FModule[$aForm]['js'] = $aModule['js'];
				$this -> FModule[$aForm]['data'] = array();
				$this -> getForm($aForm);
				$this -> getData($aForm, $aModule);
				return( true );
			} else {
    		if( $this -> FLog[1] ) $this -> log( __METHOD__ , "Access denied - " );
				$this->addJS('before','alert("Você não tem direitos para executar este módulo");');
			}
  	} else {
      if( $this -> FLog[1] ) $this -> log( __METHOD__ , "Módulo não encontrado " . $aForm );
			$this->addJS('before','alert("Módulo Não encontrado");');
		}
		return(false);
	}
	/*------------------=-----------=----------------------------------------------------------------------------
	connect             : Verifica se há conexão com o DB
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
											:boolean    : Sucesso ou Fracasso na execucao da consulta
	-----------------------------------------------------------------------------------------------------------*/
	function connect() {
		if( $this->FLog[5] ) $this -> log(  __METHOD__ );

		//------------------------------------------------
		//Criando a classe para acesso aos dados
		if( !$this->FQuery )
			$this->FQuery = new TcQuery_light( $this->FOwner.'.TcPackage', $this->FHostCon , $this->FDebug );

		return $this->FQuery -> connect();
	}
	/*------------------=-----------=----------------------------------------------------------------------------
	sqlSelect(5)        : Executa a query diretamente
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	aQuery              :string     : Query SQL
  aParam              :array      : Array de parametros (id=>1, name=>'teste');
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
											:boolean    : Sucesso ou Fracasso na execucao da consulta
	-----------------------------------------------------------------------------------------------------------*/
	function sqlSelect( $aQuery , $aParam ) {
		if( $this->FLog[5] ) $this -> log(  __METHOD__ , array( $aQuery , $aParam ) );

		// Conectando ao DB
		if( $this -> connect() ) {

			foreach( $aParam as $aCol => $aVal ) {
				$aQuery = str_replace( ':'.$aCol, $aVal , $aQuery );
			}

			return( $this->FQuery->query( $aQuery ) );
		}

		return false;

	}
	/*-----------------------------------------------------------------------------------------------------------
	getForm(1)          : Recupera o Formulário
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	aForm               :string               : Modulo a Carregar
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	-----------------------------------------------------------------------------------------------------------*/
	function getForm( $aForm ) {

		if( !isset( $this->FModule[$aForm] ) ) {
			if( $this -> FLog[1] ) $this -> log( __METHOD__ , 'Erro no módulo : ' . Array( $aForm ) );
			return( false );
		} else
			if( $this -> FLog[1] ) $this -> log( __METHOD__ , Array( $aForm ) );

		$aFileHTML  = "";													                  // Nome do arquivo
		$aContainer = "";											                  // Nome do arquivo
		$aFM   = $aForm;                                        // Guarda o Formulario
		$aMD   = ES_MODULE . substr($aForm , 0 , 3 ) . '/';     // Modulo - path

    //Carregando os arquivos auxiliares HTML
		if( isset( $this->FModule[$aForm]['form']['form'] ) ) {

			$this->FModule[$aForm]['form']['form'] = explode("," , $this->FModule[$aForm]['form']['form'] );
			$this->FModule[$aForm]['form']['container'] = explode("," , $this->FModule[$aForm]['form']['container'] );
			$this->FModule[$aForm]['main'] = array();

			// Carregando os arquivos e formularios
			foreach( $this->FModule[$aForm]['form']['form'] as $aFileHTML ) {

				$aContainer = current($this->FModule[$aForm]['form']['container']);
				if( file_exists( $aMD . $aFileHTML ) )  {
					if( $this -> FLog[9] ) $this -> log( __METHOD__ , "Incluindo... " . $aMD . $aFileHTML );
					if( isset( $this->FModule[$aForm]['main'][$aContainer] ) ) {
						$this->FModule[$aForm]['main'][$aContainer] .= file_get_contents( $aMD . $aFileHTML );
					} else {
						$this->FModule[$aForm]['main'][$aContainer] = file_get_contents( $aMD . $aFileHTML );
					}
				}

				// Necessário para limpeza correta dos comentários separar a tag css / script / html e aplicar regras distintas para cada uma
				// Está apresentando erro no seguinte trecho <svg id="Layer_1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 212 212" width="212" height="212">
				//$this->FModule[$aForm]['main'][$aContainer] = preg_replace( '/\/\/(.)*/'         , ''   , $this->FModule[$aForm]['main'][$aContainer] );  // Limpando os comentários //

				$this->FModule[$aForm]['main'][$aContainer] = preg_replace( '/<!--(\n|.)*?-->/'  , ''   , $this->FModule[$aForm]['main'][$aContainer] );  // Limpando os comentários do HTML
				$this->FModule[$aForm]['main'][$aContainer] = preg_replace( '/\/\*(\n|.)*?\*\//' , ''   , $this->FModule[$aForm]['main'][$aContainer] );  // Limpando os Comentarios /* */
				$this->FModule[$aForm]['main'][$aContainer] = preg_replace( '/(\s)+/'            , ' '  , $this->FModule[$aForm]['main'][$aContainer] );  // substituindo espaços em branco "   " => " "
				$this->FModule[$aForm]['main'][$aContainer] = preg_replace( '/\s*\>\s+\</'       , '><' , $this->FModule[$aForm]['main'][$aContainer] );  // Substituindo caracteres de espaço entre as tags
				$this->FModule[$aForm]['main'][$aContainer] = trim( $this->FModule[$aForm]['main'][$aContainer] );                                        // Trim

				$aContainer = next($this->FModule[$aForm]['form']['container']);
			}
		}

    // M = modulo
    // l = linguagem / g = grid / b = browse / f = processamento do formulario
		// c = seleciona estabelecimento / d = dados da linha do item / z = nao faz nada

    //------------------------------------------------
		//Processa a requisição conforme o tipo
		$this->processModule( $aForm ) ;

		return(true);
	}
  /*-----------------------------------------------------------------------------------------------------------
	processModule(1)    : Processa os pacotes dos modulos (m)
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	aForm               :string               : Nome do Modulo
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	-----------------------------------------------------------------------------------------------------------*/
	function processModule( $aForm ) {
    if( !(isset( $this->FModule[$aForm] ) ) ) {
      if( $this->FLog[1] ) $this -> log( __METHOD__ , "$aForm não localizado!" );
      return( false );
    } else {
      if( $this->FLog[1] ) $this -> log( __METHOD__ , $aForm );
    }

		//Definindo o módulo
		$aModule = array();
		$aModule['v']    = ( isset($this->FModule[$aForm]['cfg']['v']) ? $this->FModule[$aForm]['cfg']['v'] : '0') ;        // Versao
		$aModule['form'] = ( isset($this->FModule[$aForm]['form']) ? $this->FModule[$aForm]['form'] : array() );            // Configurações do Formulario
    $aModule['main'] = ( isset($this->FModule[$aForm]['main']) ? $this->FModule[$aForm]['main'] : '' );                 // HTML
		//------------------------------------------------
    // Carregando o JS
		if( isset( $this->FModule[$aForm]['js'] ) )
			$aModule['js'] = $this->FModule[$aForm]['js'];

		//------------------------------------------------
		//Campos do Formulario
		if( isset($this->FModule[$aForm]['field']) )
			foreach( $this->FModule[$aForm]['field'] as $i => $j )
				$aModule['field'][$i] = implode( ';' , $j );

    //------------------------------------------------
		//Campos da Busca
		if( isset( $this->FModule[$aForm]['seek'] ) ) {
			while( $i = key( $this->FModule[$aForm]['seek'] ) ) {
				$aModule['seek'][$i] = $this->FModule[$aForm]['seek'][$i]['send'];
				next( $this->FModule[$aForm]['seek'] );
			}
		}

		//Dados
		if( isset( $this->FModule[$aForm]['data'] ) )
			$aModule['data'] = $this->FModule[$aForm]['data'];
		//------------------------------------------------
    //Não Utilizar utf8_encode devido ao duplo encadeamento (json_encode) e arquivos em utf8
		$this->FReturn['pkg'][$aForm] = $aModule;

		return( true );
	}
	/*-----------------------------------------------------------------------------------------------------------
	processLanguage     : Retorna o idioma dos modulos solicitados (l)
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	aForm               :string               : Nome do Modulo
  aLang               :string               : Lingua
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	-----------------------------------------------------------------------------------------------------------*/
	function processLanguage( $aForm ) {
		if( $this->FLog[1] ) $this -> log( __METHOD__ , $aForm );
		//Variaveis - Declaracao
		if( isset( $this->FModule[$aForm]['language'] ) ) {
			$aModule = array();                 //M�dulo - Apos o processamento
			$aModule = $this->FModule[$aForm]['language'];
			$aModule['v'] = $this->FModule[$aForm]['version'];
			//------------------------------------------------
			$this->FReturn['package']['l'][$aForm] = utf8encode( $aModule );
		}
		return( true );
	}
	/*===========================================================================================================
	Manutencao/Consulta de Dados
	/*-----------------------------------------------------------------------------------------------------------
	query(5)            : Executa a query diretamente
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	aQuery              :string               : Query SQL
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
											:boolean              : Sucesso ou Fracasso na execucao da consulta
	-----------------------------------------------------------------------------------------------------------*/
	function query( $aQuery ) {
		if( $this->FLog[5] ) $this -> log(  __METHOD__ , $aQuery );

		//------------------------------------------------
		//Criando a classe para acesso aos dados
		if( !$this->FQuery )
			$this->FQuery = new TcQuery_light( $this->FOwner.'.TcPackage', $this->FHostCon , $this->FDebug );

		return( $this->FQuery->query( $aQuery ) );
	}
	/*-----------------------------------------------------------------------------------------------------------
	prepare(5)          : Executa a query através do comando prepare, execute
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	a                   :string               : Query SQL
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
											:integer              : Retorna o número de campos alternáveis
	-----------------------------------------------------------------------------------------------------------*/
	function prepare( $a ) {
		if( $this->FLog[5] ) $this -> log(  __METHOD__ , $a );

		//------------------------------------------------
		//Criando a classe para acesso aos dados
		if( !$this->FQuery )
			$this->FQuery = new TcQuery_light( $this->FHostCon , $this->FOwner.'.TcPackage' , $this->FDebug );

		return( $this->FQuery->query( $a ) );
	}
	/*-----------------------------------------------------------------------------------------------------------
	execute(5)          : Executa a query armazenada através anteriormente pelo prepare
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	a                   :array                : Campos atualizáveis (name=>'usuario',senha=>'senha')
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
											:integer              : Retorna o número de campos alternáveis
	-----------------------------------------------------------------------------------------------------------*/
	function execute( $a ) {
		if( $this->FLog[5] ) $this -> log(  __METHOD__ , $a );

		//------------------------------------------------
		//Criando a classe para acesso aos dados
		if( !$this->FQuery )
			$this->FQuery = new TcQuery_light( $this->FOwner.'.TcPackage' , $this->FHostCon , $this->FDebug );

		return( $this->FQuery->query( $a ) );
	}
}






	/*-----------------------------------------------------------------------------------------------------------
	setLanguage(4)      : Seleciona a linguagem a ser utilizada
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	aLanguage           :string               : Linguagem padrao
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	-----------------------------------------------------------------------------------------------------------*/
	function setLanguage( $aLanguage ) {
		if( $this -> FLog[4] ) $this -> log( __METHOD__ , $pLanguage );
		$this->FLanguage = $aLanguage;
	}

	/*-----------------------------------------------------------------------------------------------------------
	processFormField(1) : Processa os dados de retorno do campo (d)
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	pForm               :string               : Nome do Modulo
	pValue              :array                : Dados do formulario
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	-----------------------------------------------------------------------------------------------------------*/
	function processFormField( $aForm , $pValue ) {
		if( $this->FLog[1] ) $this->FDebug->write( get_class($this) , $this->FOwner , __FUNCTION__ );
		//Formulario de execucao da consulta
		$aForm   = $this->FModule[$aForm]['seek'][$pValue['field']]['form'];
		$aModule = array();                 //M�dulo - Apos o processamento
		$aFilter = array();                 //Armazena o filtro
		$aField  = array();                 //Armazena os campos requisitados
		$aModule['v'] = $this->FModule[$aForm]['version'];
		//------------------------------------------------
		//Recuperando o filtro
		foreach( $pValue['filter'] as $aKey => $aRow ) {
			//Convertendo os nomes dos campos quando fora de padrao
			if( isset( $this->FModule[$aForm]['seek'][$pValue['field']]['db'] ) ) {
				foreach( $this->FModule[$aForm]['seek'][$pValue['field']]['db'] as $aRowDB ) {
					if( $aKey == $aRowDB[0] or $aKey == $aRowDB[1] ) {
						$aKey = ( $aRowDB[0] == $aKey ? $aRowDB[1] : $aRowDB[0] );
						if( $this->FLog[1] ) $this->FDebug->write( get_class($this) , $this->FOwner , __FUNCTION__ , $aKey );
					}
				}
			}
			//Chave de Acesso ao Banco de Dados
			$aKeyDB = ( stripos($aKey , '_') ? '' : $this->FModule[$aForm]['table']['nick'].'_' ).$aKey;
			$aKey   = ( substr( $aKey , 0 , 6 ) == $this->FModule[$aForm]['table']['nick'] ? substr( $aKey , 7 ) : $aKey );
			$aFilter[] = $aKeyDB .'='. utf8_decode( getDelimit( ($this->FModule[$aForm]['field'][$aKey][0] == ES_VBDATE ? dateBR( '[Y]/[M]/[D]' , dateDB( $aRow ) ) : $aRow) , $this->FModule[$aForm]['field'][$aKey][0] ) );
		}
		//------------------------------------------------
		//Recuperando os campos necessarios
		foreach( $this->FModule[$aForm]['seek'][$pValue['field']]['return'] as $aKey ) {
			//Convertendo os nomes dos campos quando fora de padrao
			if( isset( $this->FModule[$aForm]['seek'][$pValue['field']]['db'] ) ) {
				foreach( $this->FModule[$aForm]['seek'][$pValue['field']]['db'] as $aRowDB ) {
					if( $aKey == $aRowDB[0] or $aKey == $aRowDB[1] ) {
						$aKey = ( $aRowDB[0] == $aKey ? $aRowDB[1] : $aRowDB[0] );
						if( $this->FLog[1] ) $this->FDebug->write( get_class($this) , $this->FOwner , __FUNCTION__ , $aKey );
					}
				}
			}
			$aField[] = $aKey;
		}
		//------------------------------------------------
		//Executando a SQL
		if( !($this->query( 'select first 1 ' .implode(',',$aField). ' from ' .$this->FModule[$aForm]['table']['browse']. ' where (' .implode(' and ' , $aFilter ). ')' ) ) ) {
			//------------------------------------------------
			//Recuperando os dados do banco
			while( $aRow = $this ->fetch_row( ) ) {
				foreach( $aRow as $aKey => $aCol ) {
					//Convertendo os nomes dos campos quando fora de padrao
					if( isset( $this->FModule[$aForm]['seek'][$pValue['field']]['db'] ) ) {
						foreach( $this->FModule[$aForm]['seek'][$pValue['field']]['db'] as $aRowDB )
							$aKey = ( ( $RowDB[0] == $aKey or $aRowDB[1] == $aKey ) ? ($RowDB[0] == $aKey ? $aRowDB[1] : $aRowDB[0]) : $aKey );
					}
					$aModule['data'][$aForm][$aKey] = ( $this->FModule[$aForm]['field'][$aKey][0] == ES_VBDATE ? dateBR( $_SESSION[ESCFPROJECT]['config']['date'] , strtotime($aCol) ) : $aCol );
				}
			}
		}
		$this->FReturn['package']['d'][$aForm] = utf8encode( $aModule );
		return( true );
	}

	/*-----------------------------------------------------------------------------------------------------------
	processGrid(1)      : Processa os pacotes dos grids
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	pForm               :string               : Nome do Modulo
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	-----------------------------------------------------------------------------------------------------------*/
	function processGrid( $aForm ) {
		if( $this->FLog[1] ) $this->FDebug->write( get_class($this) , $this->FOwner , __FUNCTION__ );
		//Variaveis - Declaracao
		$aCol    = array();                 //Armazena as colunas do retorno da query
		$aModule = array();                 //M�dulo - Apos o processamento
		$aModule['v'] = $this->FModule[$aForm]['version'];
		//------------------------------------------------
		//Recuperando o filtro

		//------------------------------------------------
		//Executando a SQL
		if( !($this->query( 'select * from ' . $this->FModule[$aForm]['table']['grid'] ) ) ) {
			//------------------------------------------------
			//Recuperando os dados da coluna
			$aCol = $this->get_col( );
			while( $aKey = key( $aCol ) ) {
				$aKey = ( substr( $aKey , 0 , 6 ) == $this->FModule[$aForm]['table']['nick'] ? substr( $aKey , 7 ) : $aKey );
				$aModule['col'][] = Array( 'field' => $aKey ,
																	 'label' => $this->FModule[$aForm]['language']['field'][$aKey][0] ,
																	 'pk'    => $this->FModule[$aForm]['field'][$aKey][3],
																	 'value' => false);
				next( $aCol );
			}
			//------------------------------------------------
			//Recuperando os dados do banco
			while( $aRow = $this ->fetch_row( true ) ) {
				$aModule['data'][] = $aRow;
			}
		}
		$this->FReturn['package']['g'][$aForm] = utf8encode( $aModule );
		return( true );
	}
	/*-----------------------------------------------------------------------------------------------------------
	processBrowse(1)    : Processa os pacotes do Browse
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	pForm               :string               : Nome do Modulo solicitor do browse
	pFormBrowse         :string               : Nome do Modulo do Browse
	pField              :string               : Nome do campo que chamou o browse
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	-----------------------------------------------------------------------------------------------------------*/
	function processBrowse( $aForm , $aFormBrowse , $pField ) {
		if( $this->FLog[1] ) $this->FDebug->write( get_class($this) , $this->FOwner , __FUNCTION__ , $aForm .'->'. $aFormBrowse );
		//Variaveis - Declaracao
		$aCol    = array();                 //Armazena as colunas do retorno da query
		$aModule = array();                 //M�dulo - Apos o processamento
		$aModule['v'] = $this->FModule[$aForm]['version'];
		$aModule['b'] = $pField;
		//------------------------------------------------
		//Recuperando o filtro
		foreach( $pValue as $aKey => $aRow ) {
			//Inserindo o apelido ao campo (Tabela principal)
			if( $pOperation!=2 ) {
				if( $this->FModule[$aForm]['field'][$aKey][3] ) {
					$aFilter[] = (stripos($aKey , '_') ? '' : $this->FModule[$aForm]['table']['nick'].'_').$aKey .'='. utf8_decode( getDelimit( ($this->FModule[$aForm]['field'][$aKey][0] == ES_VBDATE ? dateBR( '[Y]/[M]/[D]' , dateDB( $aRow ) )  : $aRow) , $this->FModule[$aForm]['field'][$aKey][0] ) );
				} else {
					$aValue[] = utf8_decode( (stripos($aKey , '_') ? '' : $this->FModule[$aForm]['table']['nick'].'_').$aKey .'='. getDelimit( ($this->FModule[$aForm]['field'][$aKey][0] == ES_VBDATE ? dateBR( '[Y]/[M]/[D]' , dateDB( $aRow ) )  : $aRow) , $this->FModule[$aForm]['field'][$aKey][0] ) );
				}
			} else {
				$aFilter[] = (stripos($aKey , '_') ? '' : $this->FModule[$aForm]['table']['nick'].'_').$aKey;
				$aValue[]  = utf8_decode( getDelimit( ($this->FModule[$aForm]['field'][$aKey][0] == ES_VBDATE ? dateBR( '[Y]/[M]/[D]' , dateDB( $aRow ) ) : $aRow) , $this->FModule[$aForm]['field'][$aKey][0] ) );
			}
		}
		//------------------------------------------------
		//Executando a SQL
		if( !($this->query( 'select * from ' . $this->FModule[$aFormBrowse]['table']['browse'] ) ) ) {
			//------------------------------------------------
			//Recuperando os dados da coluna
			$aCol = $this->get_col( );
			while( $aKey = key( $aCol ) ) {
				$aKey = ( substr( $aKey , 0 , 6 ) == $this->FModule[$aFormBrowse]['table']['nick'] ? substr( $aKey , 7 ) : $aKey );
				$aModule['col'][] = Array( 'field' => $aKey ,
																	 'label' => $this->FModule[$aFormBrowse]['language']['field'][$aKey][0] ,
																	 'pk'    => $this->FModule[$aFormBrowse]['field'][$aKey][3],
																	 'value' => false);
				next( $aCol );
			}
			//------------------------------------------------
			//Recuperando os dados do banco
			while( $aRow = $this ->fetch_row( true ) ) {
				$aModule['data'][] = $aRow;
			 }
		}
		$this->FReturn['package']['b'][$aForm] = utf8encode( $aModule );
		return( true );
	}
	/*-----------------------------------------------------------------------------------------------------------
	processForm(1)      : Processa os pacotes dos forms
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	pForm               :string               : Nome do Modulo
	pValue              :array                : Dados do formulario
	pOperation          :integer              : Operacao de Dados (0 consulta / 1 alteracao / 2 inclusao / 3 exclusao)
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	-----------------------------------------------------------------------------------------------------------*/
	function processForm( $aForm , $pValue , $pOperation ) {
		if( $this->FLog[1] ) $this->FDebug->write( get_class($this) , $this->FOwner , __FUNCTION__ );
		//Variaveis - Declaracao
		$aModule   = array();                 //M�dulo - Apos o processamento
		$aFilter   = Array();                 //Armazena o Filtro Principal
		$aModule['v'] = $this->FModule[$aForm]['version'];
		//------------------------------------------------
		//Montando o Filtro/Dados PK
		foreach( $pValue as $aKey => $aRow ) {
			//Inserindo o apelido ao campo (Tabela principal)
			if( $pOperation!=2 ) {
				if( $this->FModule[$aForm]['field'][$aKey][3] ) {
					$aFilter[] = (stripos($aKey , '_') ? '' : $this->FModule[$aForm]['table']['nick'].'_').$aKey .'='. utf8_decode( getDelimit( ($this->FModule[$aForm]['field'][$aKey][0] == ES_VBDATE ? dateBR( '[Y]/[M]/[D]' , dateDB( $aRow ) )  : $aRow) , $this->FModule[$aForm]['field'][$aKey][0] ) );
				} else {
					$aValue[] = utf8_decode( (stripos($aKey , '_') ? '' : $this->FModule[$aForm]['table']['nick'].'_').$aKey .'='. getDelimit( ($this->FModule[$aForm]['field'][$aKey][0] == ES_VBDATE ? dateBR( '[Y]/[M]/[D]' , dateDB( $aRow ) )  : $aRow) , $this->FModule[$aForm]['field'][$aKey][0] ) );
				}
			} else {
				$aFilter[] = (stripos($aKey , '_') ? '' : $this->FModule[$aForm]['table']['nick'].'_').$aKey;
				$aValue[]  = utf8_decode( getDelimit( ($this->FModule[$aForm]['field'][$aKey][0] == ES_VBDATE ? dateBR( '[Y]/[M]/[D]' , dateDB( $aRow ) ) : $aRow) , $this->FModule[$aForm]['field'][$aKey][0] ) );
			}
		}
		//Recuperando os dados
		if( $pOperation == 0 ) {
			//------------------------------------------------
			//Recuperando os dados do banco e Montando o Retorno
			if( !($this->query( 'select first 1 * from ' .$this->FModule[$aForm]['table']['table']. ' where (' .implode(' and ' , $aFilter ). ')' ) ) ) {
				while( $aRow = $this ->fetch_row( ) ) {
					foreach( $aRow as $aKey => $aCol ) {
						$aKey = ( substr( $aKey , 0 , stripos($aKey , '_') ) == $this->FModule[$aForm]['table']['nick'] ? substr( $aKey , stripos($aKey , '_') + 1 ) : $aKey );
						$aModule['data'][$aKey] = ( $this->FModule[$aForm]['field'][$aKey][0] == ES_VBDATE ? dateBR( $_SESSION[ESCFPROJECT]['config']['date'] , strtotime($aCol) ) : $aCol );
					}
				}
				//------------------------------------------------
				//Recuperando os dados dos registros filhos e Montando o retorno
				if( isset( $this->FModule[$aForm]['table']['child'] ) ) {
					if( $this->FLog[1] ) $this->FDebug->write( get_class($this) , $this->FOwner , __FUNCTION__ , 'child data');
					while( $aChild = key( $this->FModule[$aForm]['table']['child'] ) ) {
						//Executando o sql dos sub-selects
						if( !($this->query( 'select * from ' .$this->FModule[$aForm]['table']['child'][$aChild]['table']. ' where (' .implode(' and ' , $aFilter ). ')' ) ) ) {
							while( $aRow = $this ->fetch_row( ) ) {
								$aRowTmp = Array();
								foreach( $aRow as $aKey => $aCol ) {
									if( substr( $aKey , 0 , stripos($aKey , '_') ) <> $this->FModule[$aForm]['table']['nick'] ) {
										$aKey = substr( $aKey , stripos($aKey , '_') + 1 );
										$aRowTmp[$aKey] = $aCol;
									}
								}
								$aModule['datachild'][$aChild][] = $aRowTmp;
							}
						}
						next( $this->FModule[$aForm]['table']['child'] );
					}
				}
			}
		//------------------------------------------------
		//Gravando os dados no formulario - Alteracao
		} else if( $pOperation == 1 ) {
			//Atualizando os dados
			if( ($this->query( 'update ' .$this->FModule[$aForm]['table']['table']. ' set '. implode( ' , ' , $aValue ) .' where ('. implode( ' and ' , $aFilter) .')' ) ) ) {
				$aErro = $this->getError();
				$aModule['op'] = -1;
			} else {
				$aModule['op'] = 1;
			}
		//------------------------------------------------
		//Gravando os dados no formulario - Inclusao
		} else if( $pOperation == 2 ) {
			if( ($this->query( 'insert into ' .$this->FModule[$aForm]['table']['table']. '(' .implode( ' , ' , $aFilter ). ') values ('. implode( ' , ' , $aValue ) .')' ) ) ) {
				$aErro = $this->getError();
				$aModule['op'] = -1;
			} else {
				$aModule['op'] = 1;
			}
			$aModule['op'] = true;
			$this->FForm[] = $FModule;
		//------------------------------------------------
		//Atualizando os dados
		} else if( $pOperation == 3 ) {
			if( ($this->query( 'delete from ' .$this->FModule[$aForm]['table']['table']. ' where ('. implode( ' and ' , $aFilter) .')' ) ) ) {
				$aErro = $this->getError();
				$aModule['op'] = -1;
			} else {
				$aModule['op'] = 1;
			}
			$aModule['op'] = true;
			$this->FForm[] = $FModule;
		}
		$this->FReturn['package']['f'][$aForm] = utf8encode( $aModule );
	}
	/*---------------------------------------------------------------------------------------------------------
	getError(7)         : Recupera o ultimo erro
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
											:Array                : Ultimo erro
	---------------------------------------------------------------------------------------------------------*/
	function getError( ) {
		if( $this->FLog[7] ) $this->FDebug->write( get_class($this) , $this->FOwner , __FUNCTION__ );
		//Busca no TcDB o erro e se houver armazena-o
		return( $this->FQuery->getError() );
	}
	/*-----------------------------------------------------------------------------------------------------------
	fetch_row(4)        : Recupera cada linha de retorno
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	aType               :boolean              : tipo de Retorno
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
											:Array of Variant     : Dados da linha
	-----------------------------------------------------------------------------------------------------------*/
	function fetch_row( $pType = false ) {
		if( $this->FLog[4] ) $this->FDebug->write( get_class($this) , $this->FOwner , __FUNCTION__ );

		return( $this->FQuery->fetch_row( $pType ) );
	}
	/*-----------------------------------------------------------------------------------------------------------
	get_col(4)          : Recupera as colunas
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
											:Array of Variant     : Dados da linha
	-----------------------------------------------------------------------------------------------------------*/
	function get_col( ) {
		if( $this->FLog[4] ) $this->FDebug->write( get_class($this) , $this->FOwner , __FUNCTION__ );

		return( $this->FQuery->get_col() );
	}

?>
