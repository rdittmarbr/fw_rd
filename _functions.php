<?PHP
/*-------------------------------------------------------------------------------------------------------------
Funções gerais sistemas e configurações
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
Autor          : Rodrigo Dittmar
Linguaguem     : php 7.x
Licença de Uso :
-------------------------------------------------------------------------------------------------------------*/

/*=============================================================================================================
SISTEMA E CONFIGURACOES
/*------------------------------------------------------------------------------
ge_tIP              : Retorna o IP do client
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
										:string               : Dados de saida
------------------------------------------------------------------------------*/
function get_IP() {
	$aIP = '';
	if (getenv('HTTP_CLIENT_IP'))
		$aIP = getenv('HTTP_CLIENT_IP');
	else if(getenv('HTTP_X_FORWARDED_FOR'))
		$aIP = getenv('HTTP_X_FORWARDED_FOR');
	else if(getenv('HTTP_X_FORWARDED'))
		$aIP = getenv('HTTP_X_FORWARDED');
	else if(getenv('HTTP_FORWARDED_FOR'))
		$aIP = getenv('HTTP_FORWARDED_FOR');
	else if(getenv('HTTP_FORWARDED'))
		$aIP = getenv('HTTP_FORWARDED');
	else if(getenv('REMOTE_ADDR'))
		$aIP = getenv('REMOTE_ADDR');
	else
		$aIP = 'UNKNOWN';
	return $aIP;
}

/*===========================================================================================================
DATAS
/*-----------------------------------------------------------------------------------------------------------
dateBR              : Converte uma data object para um formato passado pela string
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
aString             :string               : Array para contagem de valores
																					:      d	Dia do m�s ( 1 a 31 )
																					:      D	Dia do m�s ( 01 a 31 )
																					:      r  Representa��o textual reduzida de um dia da semana ( S para Segunda, T para ter�a...)
																					:      l  Representa��o textual abreviada de um dia da semana ( Seg, ter, qua... )
																					:      L  Representa��o textual de um dia da semana ( Segunda-Feira )
																					:      m	Representa��o num�rica de um m�s ( 1 a 12 )
																					:      M  Representa��o num�rica de um m�s ( 01 a 12 )
																					:      n	Representa��o textual abreviada de um m�s (Jan, Fev... )
																					:      N	Representa��o textual de um m�s (Janeiro, Fevereiro... )
																					:      Y	Ano ( 2007 )
																					:      y	ano ( 07 )
aDate               :Object Date          : Data do sistema
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
										:string               : Extenso da data convertido
-----------------------------------------------------------------------------------------------------------*/
function date_BR( $aString = '[L], [D] de [N] de [Y]' , $aDate = false ) {

	if( !$aDate )
		$aDate = strtotime('now');

	$aMes = array();  //Mes por extenso
	$aMes[] = array( 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez' );
	$aMes[] = array( 'Janeiro', 'Fevereiro', 'Mar�o', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro' );
	$aDia = array();  //Dia por extenso
	$aDia[] = array( 'D', 'S', 'T', 'Q', 'Q', 'S', 'S' );
	$aDia[] = array( 'Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'S�b' );
	$aDia[] = array( 'Domingo', 'Segunda-Feira', 'Ter�a-Feira', 'Quarta-Feira', 'Quinta-Feira', 'Sexta-Feira', 'S�bado' );

	$aDate = str_replace( array('[d]','[D]',
															'[r]','[l]','[L]',
															'[m]','[M]',
															'[n]','[N]',
															'[y]','[Y]') ,
												array( date('j',$aDate) , date('d',$aDate) ,
															 $aDia[0][date('w',$aDate)] , $aDia[1][date('w',$aDate)] , $aDia[2][date('w',$aDate)] ,
															 date('n',$aDate) , date('m',$aDate) ,
															 $aMes[0][date('n',$aDate)] , $aMes[1][date('n',$aDate)] ,
															 date('y',$aDate) , date('Y',$aDate) ) ,
												$aString );

	return( $aDate );
}
/*-----------------------------------------------------------------------------------------------------------
dateDB              : Converte uma data do formato predefinido para data em php
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
aDate               :Object Date          : Data do sistema
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
										:string               : Extenso da data convertido
-----------------------------------------------------------------------------------------------------------*/
function date_DB( $aDate ) {
	$aDate = explode('/',str_replace("-","/",$aDate));
	$aForm = explode('/',$_SESSION[ESCFPROJECT]['config']['date']);
	$aRet  = Array('','','');
	foreach( $aForm as $aKey => $aCol ) {
		if( $aCol == '[Y]' or $aCol == '[y]' ) {
			$aRet[0] = $aDate[$aKey];
		} else if( $aCol == '[M]' or $aCol == '[m]' ) {
			$aRet[1] = $aDate[$aKey];
		} else if( $aCol == '[D]' or $aCol == '[d]' ) {
			$aRet[2] = $aDate[$aKey];
		}
	}
	return( strtotime( implode('/',$aRet) ) );
}
/*-----------------------------------------------------------------------------------------------------------
formDecode          : Funcao que explode uma string com chave
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
s                   :string               : String de dados (ex : usulgn=teste,usupass=teste,old=)
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
										:string               : array -> [usulgn]=teste [usupass]=teste [old]=_null_
-----------------------------------------------------------------------------------------------------------*/
function formDecode( $s ) {
  $s = explode(',',$s);
  $r = array();

  foreach($s as $v) {
    $v = explode(':',$v);
    $r[$v[0]]=$v[1];
  }

	return $r;
}
/*-----------------------------------------------------------------------------------------------------------
implodekey          : Funcao recursiva que realiza o implode de um vetor com suas chaves
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
a                   :string               : Caracter de Separacao
b                   :&array               : Vetor de dados
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
										:string               : array implodido
-----------------------------------------------------------------------------------------------------------*/
function implodeKey( $a , $aArray ) {
  return json_encode( $aArray );

  // Função com erro, caso ocorra um elemento filho, com valor vazio, não imprime.
	$s = '';
	//for($i = 0; $i < 1 ; $aKey => $aRow ) {
	while( $i = key($aArray) ) {
    //print(var_dump($aRow));
		$s .= "[$i]=>";
		if( is_array( $aArray[$i] ) ) {
			$s .= implode_key( $a , $aArray[$i] ) .')';
		} else {
			$s .= ((is_string($i) or is_numeric($i)) ? $i : (is_bool($i)?(($i) ? 'true' : 'false'):''));
		}
		if( next($aArray) )
			$s .= $a;
	}
	print(ES_SYTEXTEOF);
	return $s;
}
/*===========================================================================================================
Codificacao
/*-----------------------------------------------------------------------------------------------------------
utf8enconde         : Funcao recursiva que realiza a codificacao para utf8 de uma string ascii
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
aArray              :&array               : Vetor de dados
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
										:array                : Dados Convertidos
-----------------------------------------------------------------------------------------------------------*/
function utf8encode( &$aArray ) {
	if( is_array( $aArray ) )
		foreach( $aArray as $aKey => $aCol )
			$aArray[$aKey]=utf8encode($aCol);
	else
		return( utf8_encode(rtrim($aArray)) ) ;
	return($aArray);
}
/*-----------------------------------------------------------------------------------------------------------
utf8decode          : Funcao recursiva que realiza a decodificacao para utf8 de uma string ascii
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
aArray              :&array               : Vetor de dados
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
										:array                : Dados Convertidos
-----------------------------------------------------------------------------------------------------------*/
function utf8decode( &$aArray ) {
	if( is_array( $aArray ) )
		foreach( $aArray as $aKey => $aCol )
			$aArray[$aKey]=utf8decode($aCol);
	else
		return( utf8_decode($aArray) ) ;
	return($aArray);
}
/*-----------------------------------------------------------------------------------------------------------
getDelimit         : Retorna o valor + delimitador
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
aValue              :variant              : Valor para delimitar
aType               :integer              : Tipo de campo
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
										:string               : Valor com Delimitador
-----------------------------------------------------------------------------------------------------------*/
function getDelimit( $aValue , $aType ) {
	if( $aType == ES_VBVARCHAR or $aType == ES_VBDATE or $aType == ES_VBTIME )
		return( "'{$aValue}'" );
	else if( $aType == ES_VBINTEGER or $aType == ES_VBDOUBLE )
		return( $aValue + 0 );
	else
		return( $aValue );
}
/*-----------------------------------------------------------------------------------------------------------
password            : Encripta um texto
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
aString             :string               : Dados de entrada
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
										:string               : Dados de saida
-----------------------------------------------------------------------------------------------------------*/
function password( $a ) {
	return md5( crypt($a, '$1$l%ww%clks').$a );
}
function myHash( $a ) {
	return md5( crypt($a, 'clibrasUFPR').$a);
}
function my( $aFile ) {
	$aFile = pathinfo( $aFile );
	return $aFile['filename'];
}
/*/*-----------------------------------------------------------------------------------------------------------
opentext            : Abre um arquivo texto e retorna uma array(string)
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
aFile               :string               : Nome do arquivo de entrada
aType               :string = ''          : Caracter Delimitador
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
										:array(string)        : Dados de saida
-----------------------------------------------------------------------------------------------------------*/
/*function openText( $aFile , $aType = '' ) {
	vprint('teste');
	$aFiles = fopen($aFile, 'r');

  // Lendo as linhas do conteúdo
	while(!feof(  $aFiles))  {
		 $linha = fgets(  $aFiles, 1024);
  }

  // Fecha arquivo aberto
  fclose(  $aFiles);
}*/


?>
