<?PHP
/* ------------------------------------------------------------------------------------------------------------
Mantenedor     : Rodrigo Dittmar
Dependencias   : TcClass _functions _TcDebug _TcDB
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
Classe desenvolvida por Rodrigo Dittmar para o projeto SICE - Empresa ESolution Informática
Licenciado para uso sem fins comerciais para o projeto CLibras Aberta - UFPR
------------------------------------------------------------------------------------------------------------ */

/* ------------------------------------------------------------------------------------------------------------
Classe         : TcUser
Descricao      : Classe para Gerenciamento de Usuario
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
Controle de Versão
1.0.0 : 20051201 - Versão inicial
1.0.1 : 20060401 - Otimização do código
1.0.2 : 20060501 - Otimização do código e correção de BUGS
1.2.0 : 20061001 - Implementacao da classe para o AJAX, Otimizacao
1.3.0 : 20070331 - Adaptacao a classe TcDB
1.3.0 : 20070630 - Correcao Codificacao Erros/Definicao do Log Debug em Niveis
                   Niveis de Debug
                    0 - Construtores / Destrutores
                    1 - Login do Usuario
                    2 - Logout e Revalidacao do Usuario / active
                    9 - Auxiliares / Privadas / Sem muita importancia
1.5.0 : 20191201 - Adaptação da Classe para o sistema CLibras
                   Atualização do Log de Acesso - Gravado no DB
                   Padronização da nomenclatura de variáveis
                   Mascaramento da sessão conforme o nome do Projeto (MD5)
                   Definição de funções privadas
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
$FConfig = array('multiple' => false,        // Múltiplas instâncias
                 'timeout'  => 6000 ,        // Timeout
                 'project'  => null ,        // Dados do projeto
                 'log'      => array('access'   => true ,
                                     'password' => true ),

                 'pass'     => array(3,25),         // Tamanho da senha mínimo, máximo
                 'validate' => array('email','register','code','rf') );
------------------------------------------------------------------------------------------------------------ */
class TcUser {

  private $FOwner = '';                //Proprietario da classe (nome de quem instanciou)
  private $FDB;                        //Classe de acesso ao TcDB
  private $FQuery;                     //Classe de execução da Query TcQuery
  private $FSession;                   //Nome do cookie para armazenar a seção
  private $FConfig = array();          //Vetor de Configurações

  private $FIP = '';                   //Endereço IP - ipv4/ipv6 do usuário

  private $FDebug = false;             //recurso para gravar log do debug
  private $FDebugLevel = array();      //Niveis de gravacao do debug

	/* -----------------=-----------=----------------------------------------------------------------------------
  __construct(0)      : Cria a classe e atualiza os atributos
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aOwner              :string      : Proprietario do Objeto
  aConfig             :array       : Array de Configuração (modelo $esDB)
  aTcDB               :&TcDB       : Objeto Banco de Dados
  aDebug              :&tcDebug    : Objeto Debug
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  ---------------------------------------------------------------------------------------------------------- */
  function __construct( $aOwner = '' , $aConfig , &$aTcDB , &$aTcDebug = false ) {

    $this -> FOwner = $aOwner;            // Proprietário da Classe
		$this -> FDB    = $aTcDB;             // Objeto TcDB

    $this -> FDebug         = $aTcDebug;                  // Objeto TcDebug
    $this -> FDebugLevel    = array(0,0,0,0,0,0,0,0,0,0); //Nivel do Log/Debug
    $this -> FConfig = array('multiple' => true,          // Múltiplas instâncias
                             'timeout'  => 6000 ,         // Timeout - Login
                             'project'  => null ,         // Dados do projeto
                             'log'      => array('access'   => true ,
                                                 'password' => true ),
                             'validate' => array('email','register','code')
                           ); //,'rf') );

    //Setando os parametros da classe -- Apenas o que for necessário alterar
    if( is_array($aConfig) ) {
      foreach ($aConfig as $a=>$b) {
        $this -> FConfig[strtolower($a)] = $b;
      };
    }
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // Definindo a variável da sessão
    $this -> FSession = md5($this -> FConfig['project']);
    session_name( $this -> FSession ) ;
    session_set_cookie_params ( 0 , $this -> FSession );
    session_start();

    //Definindo os Niveis de Backup
    if( is_object($this -> FDebug) ) {
      $this -> FDebugLevel = $this -> FDebug -> FWrite;
      $this -> logDebug( __METHOD__ , 'Debug Level :' . implode($this -> FDebugLevel,",") );
    }
  }
	/* -----------------=-----------=----------------------------------------------------------------------------
  __destruct(0)       : Libera a memoria alocada e encerra a coneccao
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  -----------------------------------------------------------------------------------------------------------*/
  function __destruct() {
    unset( $this -> FQuery );
    if( $this -> FDebugLevel[0] ) $this -> logDebug( __METHOD__ );
  }
	/* -----------------=-----------=----------------------------------------------------------------------------
  logDebug            : Armazena o Log de Debug
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aFunction           :string     : Evento da Classe
  aDes                :string     : Descricao do Evento
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  ---------------------------------------------------------------------------------------------------------- */
  private function logDebug( $aFunction , $aDes = '' ) {
    $this -> FDebug -> log( $this -> FOwner, $aFunction , $aDes );
  }
	/* -----------------=-----------=----------------------------------------------------------------------------
  logDB(9)             : Adiciona o log de acesso ao DB
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aType                : Tipo de LOG
                         1 - Log de Acesso / Login / Logout
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  -----------------------------------------------------------------------------------------------------------*/
  private function logDB( $aType ) {

    // Conectando ao DB
    if( !$this ->FQuery ) {
      $this -> FQuery = new TcQuery_light( $this -> FOwner.'.TcUser' , $this -> FDB , $this -> FDebug );
    }

    if( $this -> FConfig['log']['access'] ) {

    } else if ( $this -> FConfig['log']['login'] == "Y" ) {
    }

  }
	/* -----------------=-----------=----------------------------------------------------------------------------
  setTimeOut          : Verifica o Tempo de Login e atualiza evitando o timeout
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aUser               :string     : Nome do Usuario
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  ---------------------------------------------------------------------------------------------------------- */
  private function setTimeOut( $aTime ) {
    if( $this -> FDebugLevel[0] ) $this -> logDebug( __METHOD__ , $aTime );
    $this -> $FConfig['timeout'] = $aTime;
  }
  /* ==========================================================================================================
  Validacao do Usuario
	/* -----------------=-----------=----------------------------------------------------------------------------
  login(1)            : Loga o usuario ao sistema
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aUser               :string     : Nome do Usuario
  aPass               :string     : Senha
  aRelogin            :bool       : Primeiro acesso ao sistema
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
                      :Boolean    : Sucesso ou Fracasso
  ---------------------------------------------------------------------------------------------------------- */
  function login( $aUser , $aPass , $aRelogin = false ) {

    //Limites do campo de Login
    if( ( ( strlen( trim($aUser) ) <= ES_LOGINPASS_MIN ) or ( strlen( trim($aUser) ) > ES_LOGINPASS_MAX ) ) or
        ( ( strlen( trim($aPass) ) <= ES_LOGINPASS_MIN ) or ( strlen( trim($aPass) ) > ES_LOGINPASS_MAX ) ) ) {
      if( $this -> FDebugLevel[1] ) $this -> logDebug( __METHOD__ , 'error login/password length (ES_LOGINPASS_MIN .. ES_LOGINPASS_MAX)' );
      return( false );
    }

    if( $this -> FDebugLevel[1] ) $this -> logDebug( __METHOD__ , ( $aRelogin?"Relogin":"" ) );

    // Caso solicite apenas validacao de usuario
    if( $aRelogin ) {
      return( $this -> active() );
    }

    //Criando a Classe de acesso ao DB
    if( !$this ->FQuery ) {
      $this -> FQuery = new TcQuery_light( $this -> FOwner.'.TcUser' , $this -> FDB , $this -> FDebug );
    }

    $aCol = $this -> FConfig['validate'];
    foreach( $aCol as $c => $v ) {
      $aCol[$c] = $v . " = '{$aUser}'";
    }
    $aQuery = "select idUser, name, code, password, SocialName, avatar, skin from cl_user " .
              "  where ( " . implode(' or ', $aCol ) . " ); ";

    //Inicializando os Recursos e validando o login
    if( $this -> query( $aQuery ) == 0 ) {

      //Recuperando a Primeira linha de dados
      $aRow = $this -> FQuery -> fetchRow();

      if( $this -> FDebugLevel[9] ) $this -> logDebug( __METHOD__ , $aRow );

      //Validando a Senha
      if ( strcmp( trim($aRow['password']) , trim(password($aPass)) ) == 0 ) {
        if( $this -> FDebugLevel[1] ) $this -> logDebug( __METHOD__ , "login sucessfull" );
        $_SESSION['id']   = $aRow['iduser'];       // id do usuário
        $_SESSION['user'] = $aRow['code'];         // Usuário
        $_SESSION['ip']   = get_IP();              // Endereço IP
        $_SESSION['login']   = time();             // Horario do Ultimo login
        $_SESSION['lupdate'] = time();             // Horario da atualização do sistema

        // Definindo os módulos acessíveis
        // Criar a rotina de consulta SQL - Verificar os atributos e direitos
        $_SESSION['modules'] = ['usudta' => true];
        return(true);
      } else
        if( $this -> FDebugLevel[1] ) $this -> logDebug( __METHOD__ , "user error (".password($aPass).")");

    } else
      if( $this -> FDebugLevel[1] ) $this -> logDebug( __METHOD__ , "error in select (".$aQuery.")");
    return(false);
  }
	/* -----------------=-----------=----------------------------------------------------------------------------
  Logout(1)           : Finaliza a conexao do Usuario
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aCause              : string    : Motivo
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  ---------------------------------------------------------------------------------------------------------- */
  function logout( $aCause = '' ) {
    if( $this -> FDebugLevel[1] )
      $this -> logDebug( __METHOD__ , $aCause );

    if( isset($_COOKIE[session_name()]) ) {
      setcookie(session_name(), '', time()-42000, '/');
    }

    $_SESSION = array();
    @session_unset();
  	@session_destroy();
  }
	/* -----------------=-----------=----------------------------------------------------------------------------
  active(2)           : Verifica se a secao esta ativa e nao expirou
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
                      :Boolean    : Ativo ou Inativo
  ---------------------------------------------------------------------------------------------------------- */
  function active() {

    //Verificando se a sessao esta ativa
    if( isset($_SESSION['user']) ) {

      if( $this -> FDebugLevel[2] ) $this -> logDebug( __METHOD__ , $_SESSION );
      if( $this -> FDebugLevel[2] ) $this -> logDebug( __METHOD__ , $this -> FConfig );

      //Executa o logout se expirar o tempo
      if( ($_SESSION['lupdate'] + $this -> FConfig['timeout']) > time() ) {
        $_SESSION['lupdate'] = time();
        return( true );
      } else {
        $this -> logout('Session timeout');
      }
    } else {
      if( $this -> FDebugLevel[2] ) $this -> logDebug( __METHOD__ , 'not logged in' );
    }
    return( false );

  }
  function reLogin( ) {      //Mantido apenas para retrocompatibilidade
    return( $this -> active() );
  }
  /*===========================================================================================================
  Funcoes Auxiliares
	/* -----------------=-----------=----------------------------------------------------------------------------
  query(9)            : Executa uma query
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aQuery              :string      : Query SQL
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
                      :Boolean     : Sucesso ou Fracasso
  ---------------------------------------------------------------------------------------------------------- */
  function query( $aQuery ) {
    if( $this -> FDebugLevel[9] ) $this -> logDebug( __METHOD__ , $aQuery );
    return( $this -> FQuery -> query( $aQuery ) );
  }
	/* -----------------=-----------=----------------------------------------------------------------------------
  Funcao : newpassword  - Cria e retorna uma senha temporaria
  Entrada: aLen         - Tamanho da Senha
  Saida  : STRING       - Retorna a senha temporaria
  ---------------------------------------------------------------------------------------------------------- */
  function newPassword($aLen = 7) {
    $apass="";                     //Armazena a Senha

    for( $i=0; $i < $aLen; $i++){
      $char=rand(48,122);
      if ( ($char > 97 && $char < 122) ||
           ($char > 65 && $char <  90) ||
           ($char > 48 && $char <  57) )
         $apass.=chr($char);
      else
        $i--;
    }

    return ($apass);
  }
	/* -----------------=-----------=----------------------------------------------------------------------------
  Funcao : newpassword  - Cria e retorna uma senha temporaria
  Entrada: aLen         - Tamanho da Senha
  Saida  : STRING       - Retorna a senha temporaria
  ---------------------------------------------------------------------------------------------------------- */
  function setPassword($aLen = 7) {
    $apass="";                     //Armazena a Senha

    for( $i=0; $i < $aLen; $i++){
      $char=rand(48,122);
      if ( ($char > 97 && $char < 122) ||
         ($char > 65 && $char <  90) ||
         ($char > 48 && $char <  57) )
         $apass.=chr($char);
      else
        $i--;
    }
    return ($apass);
  }
}
