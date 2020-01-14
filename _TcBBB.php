<?PHP
/*------------------------------------------------------------------------------
Autor          : Rodrigo Dittmar
Linguaguem     : php 7.x
Dependencias   : Class(TcDebug)
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
Modulo         : Coneccao ao sistema BBB
------------------------------------------------------------------------------*/

/*------------------------------------------------------------------------------
Classe         : TcBBB
Descricao      : Coneccao ao Sistema BBB
--------------------------------------------------------------------------------
Versao 1.0.0.0 : 2019     -
------------------------------------------------------------------------------*/
Class TcBBB {

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
  aDebug              :&tcDebug   : Objeto Debug
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  ----------------------------------------------------------------------------*/
  function __construct( $aOwner = '' , $aConfig = false , &$aDebug = false ) {

    //Definindo as propriedades do objeto
    $this -> FOwner = $aOwner;
    $this -> FDebug = $aDebug;
    $this -> FWrite = array(0,0,0,0,0,0,0,0,0);

    //Setando os parametros da classe -- Apenas o que for necessário alterar
    if( is_array($aConfig) ) {
      foreach ($aConfig as $a=>$b) {
        $this -> FConfig[strtolower($a)] = $b;
      };
    }

    //Definindo os Niveis de Backup
    if( ES_SYDEBUG and is_object($this -> FDebug) ) {
      $this -> FWrite = $this -> FDebug -> FWrite;
      $this -> write( __METHOD__ , 'Debug Level :' . implode($this -> FWrite,",") );
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

    if( $this -> FWrite[0] ) $this -> write( __METHOD__ );
  }
  /*----------------------------------------------------------------------------
  write               : Armazena o Log de Debug
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aFunction           :string     : Evento da Classe
  aDes                :string     : Descricao do Evento
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  ----------------------------------------------------------------------------*/
  function write( $aFunction , $aDes = '' ) {
    $this -> FDebug -> write( $this -> FOwner, $aFunction , $aDes );
  }
  /*------------------=-----------=---------------------------------------------------------------------------=
  status              : Retorna o satus da classe
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  -----------------------------------------------------------------------------------------------------------*/
  function status( ) {

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
}
