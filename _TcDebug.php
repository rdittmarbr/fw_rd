<?PHP
/*------------------------------------------------------------------------------
Autor          : Rodrigo Dittmar
Linguaguem     : php 7.x
Dependencia    : functions.php
Licença de Uso :
------------------------------------------------------------------------------*/

/*------------------------------------------------------------------------------
Classe         : TcDebug
Descricao      : Cria um arquivo texto contendo o debug das classes
--------------------------------------------------------------------------------
Versao 1.0.0.0 : 20060401
Versao 1.0.0.1 : 20061231 - Inclusao da opcao para salvar o Cache
Versao 1.0.0.2 : 20070331 - Correcao de Erros / Otimizacao do codigo
Versao 1.0.0.3 : 20120315 - Nivel de backup (Definicao DebugLevel)
                            Niveis de Debug
                                0 - Construtores / Destrutores
                                1 - Manutencoes Principais ( - Sem as quais nao funciona a class)
                                2 - Funcoes Secundarias ( Adicao de campo / alteracao de valores)
                                5 - ...
                                9 - Auxiliares / Privadas / Sem muita importancia
Versao 1.0.0.4 : 20120315 - Correcao do Nivel de backup
Versao 1.0.1.0 : 2019     - Adaptação para o projeto CLibras
------------------------------------------------------------------------------*/
Class TcDebug extends stdClass {

  private $FOwner     = '';             //Proprietário
  private $FDebug     = array();        //Armazena as chamadas do debug na memoria para posterior gravação
  private $FTimeStart = 0;              //Tempo de Inicio da Classe

  // Configuração da Classe
  private $FConfig    = '';             //Configuração da Classe
  public  $FWrite     = array();        //Nivel de Gravação do Debug
  private $FWrited    = false;          //Verifica se já houve a gravação do log, impedindo assim de gravar o cabeçalho
  /*----------------------------------------------------------------------------
  __construct         : Cria a classe e atualiza os atributos
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aOwner              :string      : Proprietario do Objeto
  aConfig             :array       : Array de Configuração
                                     (enviar somente o que houver necessidade de alterar)
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  ----------------------------------------------------------------------------*/
  function __construct( $aOwner = '' , $aConfig = false ) {

    $this -> FOwner     = $aOwner;                    // Proprietário da Classe
    $this -> FTimeStart = microtime(true);            //Setando o Tempo Inicial da Classe
    $this -> FWrite     = array(0,0,0,0,0,0,0,0,0,0);

    $this -> FConfig = array( 'ip' => get_IP(),           //Endereço IP do Client
                              'file' => '',               //Arquivo para gravacao do debug
                              'config' => ES_SYCONFIG );  //Imprime a Configuracao do Site

    //Setando os parametros da classe - Apenas o que for necessário alterar
    if( is_array($aConfig) ) {
      foreach ($aConfig as $a=>$b) {
        $this -> FConfig[strtolower($a)] = $b;
      };
    }

    //Setando o Nível do Debug
    for( $i = 0 ; $i<10 ; $i++ ) { $this -> FWrite[$i] = ( ES_DEBUGLEVEL >= $i ); }

    //Gravando o debug
    $this -> write( $this -> FOwner , __METHOD__ , 'System Debug Level = ' . ES_DEBUGLEVEL );

    // Definindo o nome do Arquivo
    $this -> setFile();
  }
  /*----------------------------------------------------------------------------
  __destruct          : Finaliza a classe e fecha o arquivo se necessario
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  ----------------------------------------------------------------------------*/
  function __destruct() {
    $this -> write( $this -> FOwner , __METHOD__ );

    if( count($this->FWrite) > 0 ) {
      $this -> save();
    }
  }
  /*----------------------------------------------------------------------------
  write               : Armazena o Log de Debug
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aClass              :string      : Classe
  aOwner              :string      : Nome da Classe Propriet�ria
  aDes                :string      : Descricao do Evento
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  ----------------------------------------------------------------------------*/
  function write($aOwner , $aClass , $aDes = '' ) {
    $this -> FDebug[] = array( date('Ymd-H:i:s') , microtime(true) , $aOwner , $aClass , $aDes , false);
  }
  function log($aOwner , $aClass , $aDes = '' ) {
    $this -> FDebug[] = array( date('Ymd-H:i:s') , microtime(true) , $aOwner , $aClass , $aDes , false);
  }
  function security($aOwner , $aClass , $aDes = '' ) {
    $this -> FDebug[] = array( date('Ymd-H:i:s') , microtime(true) , $aOwner , $aClass , $aDes , true);
  }
  /*----------------------------------------------------------------------------
  setFile             : Altera o Arquivo a Gravar o Log
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aFile               :string      : Novo Arquivo
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  ----------------------------------------------------------------------------*/
  function setFile( $aFile = false ) {
    $aFile = ( $aFile ? $aFile : $this -> FOwner .'('.date('md-H').').txt');

    $this -> FConfig['file'] = str_replace('//','/', ES_LOG . $aFile);
    $this -> write( $this -> FOwner , __METHOD__ , $this -> FConfig['file'] );

    // define que não houve gravação do log, para possibilitar a gravação em novo arquivo.
    $this -> FWrited = false;
  }
  /*----------------------------------------------------------------------------
  save                : Salva o arquivo de LOG
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  aforce              : boolean : força a gravação do log e limpa se concluído
  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  ----------------------------------------------------------------------------*/
  function save( $aForce = false ) {

    $this -> write( $this -> FOwner , __METHOD__ , "Linhas : " . count($this-> FDebug));

    //Gravando o LOG
    $aFile = @fopen( $this -> FConfig['file'] , 'a+' );

    //Erro ao abrir o arquivo de log
    if( !($aFile) or !(is_writable($this -> FConfig['file']) ) ) {
      return false;
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // Grava o cabecalho do log apenas se não houve gravação do cabeçalho antes
    if( ! $this -> FWrited ) {
      fwrite($aFile, str_pad('',60,'-') . ES_SYTEXTEOF );
      fwrite($aFile , "IP : " . $this -> FConfig['ip'] . "(".ES_DEBUGLEVEL.")" . ES_SYTEXTEOF );
      if ( $this -> FConfig['config'] ) {
        fwrite($aFile, str_pad('',60,'-') . ES_SYTEXTEOF );
        fwrite($aFile, 'ES_PATH  :' . ES_PATH . ES_SYTEXTEOF  );
        fwrite($aFile, 'ES_LIB   :' . ES_LIB . ES_SYTEXTEOF  );
        fwrite($aFile, 'ES_HTML  :' . ES_HTML . ES_SYTEXTEOF  );
        fwrite($aFile, 'ES_LOG   :' . ES_LOG . ES_SYTEXTEOF );
        fwrite($aFile, 'ES_MODULE:' . ES_MODULE . ES_SYTEXTEOF );
        fwrite($aFile, str_pad('',60,'-') . ES_SYTEXTEOF );
        foreach (getallheaders() as $name => $value) {
          fwrite($aFile, $name .':' . $value . ES_SYTEXTEOF );
        }
      }
      fwrite($aFile, str_pad('',60,'-') . ES_SYTEXTEOF );
      $this -> FWrited = true;
    }

    foreach($this -> FDebug as $aRow) {
      fwrite($aFile, $aRow[0] ."(". str_pad($aRow[1],15,'0') ." - ". number_format($aRow[1]-$this -> FTimeStart,5).") - ");
      fwrite($aFile, str_pad($aRow[2].".".$aRow[3],60) .";" );
      fwrite($aFile, $a = str_replace( chr(10) , '' , str_replace( chr(13) , '' , (is_array($aRow[4]) ? implodeKey( '; ' , $aRow[4] ) : $aRow[4] ) ) ) );
      fwrite($aFile , ES_SYTEXTEOF );
    }
    //Gravando o Fim do Log - para efeito de contagem de tempo
    fwrite($aFile, date('Ymd-H:i:s') ."(". str_pad(microtime(true),15,'0') ." - ". number_format(microtime(true) - $this->FTimeStart,5).") - ");
    fwrite($aFile, str_pad($this->FOwner.".".get_class($this)."::".__METHOD__.".EOF - FILE",60) .";" );
    fwrite($aFile , ES_SYTEXTEOF );
    fclose( $aFile );

    if( $aForce ) {
      $this->FDebug = array();
    }

  }
}
?>
