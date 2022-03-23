<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;
use \Bitrix\Main\Application;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

CBitrixComponent::IncludeComponentClass("cottonclub:cottonclub.portal.core");

class CCottonclubPortaljurnalred extends CCottonclubPortalCore {
	protected $serviceName = "cottonclub-portal-sw-journal-red";
	protected $includeTemplate = false;//Подключаем шаблон в initServiceCommand
	protected $needAuth = true; // проверка авторизации
	protected $skipGlobalDBlog = true;// не ведем журал обработки
 	public function onPrepareComponentParams($arParams)
    {    
    	return $arParams;
    }
      public function peresort(){
     	$arResult=$this->arResult;
    	$fields=$arResult['fields'];
    	$row2=[];
    	foreach($arResult['row'] as $key=>$val){
			$arUserType=$arResult['fields'][$key];
			$row2[$arUserField['SORT']][] =array("ID"=>$key) ;
		}
		ksort($row2);
		$arResult['fields']=[];
		foreach($row2 as $tt){
			foreach($tt as $VV){
				if($VV['ID']=='ID')
					continue;
				$arResult['fields'][$VV['ID']]=$fields[$VV['ID']];
			}
		}
		//AddMessage2Log(print_r ($arResult ,true), 0 , 0);
		return $arResult;

    }

	  public function provnadostuppropnapokaz() { 
	   //$arrprop массив рарешенных полей
		$arResult = $this->arResult;
		$arParams = $this->arParams;
		$arrprop=$arParams["DETAIL_SHOW"];


		foreach($arResult['row'] as $key=>$val){

			if( !in_array($key, $arrprop) && $key <> 'ID'){
	  			unset($arResult['fields'][$key]);
	  			unset($arResult['row'][$key]);
	  			continue;
			}
			else{//Если есть то заполяем значениями

  				if (isset($_REQUEST[$key]) && $key!=='ID'){
						$arResult['row'][$key]=$_REQUEST[$key];	
						if(!$arResult['fields'][$key]['VALUE']){
							$arResult['fields'][$key]['VALUE']=$_REQUEST[$key];
						}						  				
	  			}
	  			elseif($key!=='ID' && !$arParams['ROW_ID']){
	  				$arResult['row'][$key]=$val->getDefault_value;					
	  			}	
	  			elseif($key=='ID' && !$arParams['ROW_ID']){
	  				$arResult['row'][$key]='new';
	  			}
	  			elseif($key!=='ID'){
	  				$arResult['row'][$key]="";
	  			}

	  		}
	
		}	
			//AddMessage2Log(print_r ($arResult ,true), 0 , 0);
		return $arResult;

    }

    public  function provdostuppropnared($arrpardop,$arGroups,$znach) {
    	// $znach - Массив с значениями доступа есть есть в массивах и если нет
    	// $arrpardop - Массив групп разрешенных
    	// $arGroups - Массив групп пользователя

			$arResult = $this->arResult;
			foreach($arResult['row'] as $key=>$val){
				//AddMessage2Log(print_r ($key ,true), 0 , 0);
				//AddMessage2Log(print_r ($val ,true), 0 , 0);
				if(key=="ID" && $val=="new"){
					$arResult['fields'][$key]['DOSTUP']=$znach[0];
				}
				else if( count(array_intersect($arrpardop[$key],$arGroups))<=0 ){
					$arResult['fields'][$key]['DOSTUP']=$znach[1];
				}
				else{//Если есть				
					$arResult['fields'][$key]['DOSTUP']=$znach[0];
	  			}	
			}	
			//AddMessage2Log(print_r ($arResult ,true), 0 , 0);
		return $arResult;
    }

    public function dostupuserDepartamet() {


    }
    public static function updateufplandatajurnal($elid,$hlblock_id,$arrpropupdate,$rowcence ) {
    	//$elid - id Элемента
    	//$hlblock_id - Блок ID
    	//$arrpropupdate - Массив обновляемых даных
    	//$rowcence - Массив первоночальных значений

			if(isset($arrpropupdate["UF_PLAN"])){
				global $USER;
							$hlblock = HL\HighloadBlockTable::getById($hlblock_id)->fetch();
							$entity = HL\HighloadBlockTable::compileEntity($hlblock);
							$entity_data_class = $entity->getDataClass();
							$propupdateplan=[];
							$propupdateplan["UF_PLAN_LIST"]=$rowcence["UF_PLAN_LIST"];
							if($arrpropupdate["UF_PLAN"]){//если есть то пишем какую устанавливаем
								$propupdateplan["UF_PLAN_LIST"][]=$arrpropupdate["UF_PLAN"];
							}
							else{
								$objDateTime = new DateTime('2000-01-01');// Если дата удаляется то устанавливаем хоть какую дату чтобы зафиксировать факт изменения
								$propupdateplan["UF_PLAN_LIST"][]=$objDateTime->format("d.m.Y H:i:s");
							}

							//Устанавливаем текущее время изменений
							$objDateTime = new DateTime();
							$propupdateplan["UF_PLAN_LIST_CHENHE"]=$rowcence["UF_PLAN_LIST_CHENHE"];
							$propupdateplan["UF_PLAN_LIST_CHENHE"][]=$objDateTime->format("d.m.Y H:i:s");
							//Фиксируем пользователя
							$propupdateplan["UF_PLAN_USER_LIST"]=$rowcence["UF_PLAN_USER_LIST"];
							$propupdateplan["UF_PLAN_USER_LIST"][]=$USER->GetID();
							//И еще рез обновляем чтобы записать изменения
							$result = $entity_data_class::update($elid, $propupdateplan);
			}

    }
    //
    public function chengeValdataprop($val,array $proprt = array(),array $rowcence = array(),$udate = false) {
						//$proprt - Массив с настройками свойства

						$key=$proprt["FIELD_NAME"];
    		 		//Если тип файл то проверяем небыл ли он удален в процессе если был то тут же его удаляем с сервера
						if($proprt["USER_TYPE_ID"]=='file'){
							if(is_array($_REQUEST[$key."_del"])){
								foreach($_REQUEST[$key."_del"] as $val2){
									CFile::Delete($val2);
									if($udate=="Y"){
										if(in_array($val2,$this->arResult["row"][$key])===true){
											//$keydel=array_keys($this->arResult["row"][$key],$val2);
											$ttt=array_diff($this->arResult["row"][$key],array($val2));
											//AddMessage2Log(print_r ($ttt ,true), 222 );	
											$this->arResult["row"][$key]=$ttt;
										}
										

									}
								}
							}
						}
						elseif($proprt["USER_TYPE_ID"]=="datetime"){
							//проверка	на коректность даты				
							if(\Bitrix\Main\Type\DateTime::isCorrect($val,"d.m.Y H:i")===false && $val){
									$this->arResult['ERROR'].="<br>Не корректно введена дата".$val;
							}
							elseif($udate=="Y" && $rowcence[$key] && $val){
							 	if($rowcence[$key]){
							 		$datget=$rowcence[$key]->getTimestamp();
							 		$datset= new \Bitrix\Main\Type\DateTime($val,"d.m.Y H:i");
							 		$datset=$datset->getTimestamp();
							 		if($datset == $datget){
							 			unset($this->arResult["row"][$key]);
							 		}
							 	}
							}
					}
					else{
						if($udate=="Y"){
							
						}

					}

    }
    public function arraychengeValdataprop(array $arrpropupdate = array(),array $rowcence = array(),$udate = false) {
    	$arResult = $this->arResult;
//AddMessage2Log(print_r ($arResult ,true), 0 , 0);
			if(count($arrpropupdate)<=0){
				$this->arResult['ERROR'] .="<br>Нет данных для передачи ";
			}
			else{
					foreach($arResult["row"] as $key=>$res){

						$proprt=$arResult['fields'][$key];
						if(!is_array($proprt)){
							continue;
						}
						//AddMessage2Log(print_r ($key ,true), 0 , 0);
						//

						//Проверка на обязательность
						if($proprt['MANDATORY']=="Y" && $proprt["DOSTUP"]=="R"){
		 					if(!$arrpropupdate[$key]){
								$this->arResult['ERROR'] .="<br>Не заполнено обязательное поле ";
								if($proprt["LIST_COLUMN_LABEL"]){
									$this->arResult['ERROR'] .=$proprt["LIST_COLUMN_LABEL"].";<br>";
								}
								else{
									$this->arResult['ERROR'] .=$proprt["FIELD_NAME"].";<br>";
								}
		 					}
		 				}
		 				if($proprt["DOSTUP"]=="N"){
							unset($this->arResult["row"][$key]);
		 					//AddMessage2Log(print_r ($proprt ,true), 0 , 0);
		 				}
		 				//Если в передаточном массиве есть значения их надо обработать
		 				if(isset($arrpropupdate[$key])){
		 					//Если предполагается массив
		 					if($proprt['MULTIPLE']=="Y"){
		 						foreach($arrpropupdate[$key] as $keyval=>$val){
		 							self::chengeValdataprop($val,$proprt,$rowcence,$udate);

		 						}
		 							if($udate == "Y"){
		 								$flagiz=[];
										$arrpropupdate[$key]=$this->arResult["row"][$key];
										if(is_array($arrpropupdate[$key]) && is_array($rowcence[$key])){			 									
			 									$flagiz=array_diff($arrpropupdate[$key], $rowcence[$key]);
			 									if(count($flagiz)<=0){
			 										$flagiz=array_diff($rowcence[$key],$arrpropupdate[$key]);
			 									}
										}
										else{
											if(!is_array($arrpropupdate[$key]) && count($rowcence[$key])>0){
												$flagiz[]=1;
											}
										}
										//AddMessage2Log(print_r ($_REQUEST ,true), 1 );
										//AddMessage2Log(print_r (array($key=>$flagiz) ,true), 1 );								
										//AddMessage2Log(print_r ($this->arResult["row"][$key] ,true), 2 );
										//AddMessage2Log(print_r ($rowcence[$key] ,true), 3 );

										if(count($flagiz)<=0){
												unset($this->arResult["row"][$key]);					
										}		

		 							}

		 					}
		 					else{
		 							self::chengeValdataprop($arrpropupdate[$key],$proprt,$rowcence,$udate);//,$rowcence,$udate
		 							if($udate == "Y"){
		 								if($res==$rowcence[$key] || $res==false && $rowcence[$key]==false || $res === $rowcence[$key]){
		 									unset($this->arResult["row"][$key]);
		 								}
										//AddMessage2Log(print_r ($arrpropupdate[$key] ,true), 0 , 0);
										//AddMessage2Log(print_r ($rowcence ,true), 0 , 0);
		 							}
		 					}
		 				}
		 				else{
		 					unset($this->arResult["row"][$key]);
		 				}
	

					}
			}
			//return $arResult;
    }

	public function execute() {
    	$this->initServiceCommand();

    	$this->__includeComponent();

	}


}
?>