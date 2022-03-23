<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();


$requiredModules = array('highloadblock');
foreach ($requiredModules as $requiredModule)
{
	if (!\Bitrix\Main\Loader::includeModule($requiredModule))
	{
		ShowError(GetMessage('F_NO_MODULE'));
		return 0;
	}
}

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;


global $USER_FIELD_MANAGER;
global $USER;
$arGroups = $USER->GetUserGroupArray();

//Собираем массивы доступа
$arrpardop2=[];
$arrpardop=[];
foreach($arParams as $key=>$val):
    if(substr($key, 0, 13)=="PERMISSION_UF"){
    	//$this->pp($val);
    	$codpole=substr($key, 11);
    	if(is_array($val)){
        	$arrpardop2=array_merge($arrpardop2,$val);
        }
        $arrpardop[$codpole]=$val;
    }
endforeach;


$DOSTUPADD = count(array_intersect($arGroups, $arParams["PERMISSION_ADD_GROOPS"])) > 0 ;

$bAllowAccess = count(array_intersect($arGroups, $arrpardop2)) > 0 ;
//echo '<pre>';print_r($bAllowAccess);echo '</pre>';

$arResult['ERROR']  = '';

if(!$bAllowAccess){
	$arResult['ERROR'] = "Нет доступа к редактированию";
}

// hlblock info
$hlblock_id = $arParams['BLOCK_ID'];

if (empty($hlblock_id))
{
	$arResult['ERROR'] = GetMessage('HLBLOCK_VIEW_NO_ID');
}
else
{
	$hlblock = HL\HighloadBlockTable::getById($hlblock_id)->fetch();
	if (empty($hlblock))
	{
		$arResult['ERROR'] = GetMessage('HLBLOCK_VIEW_404');
	}
}

// check rights

if (isset($arParams['CHECK_PERMISSIONS']) && $arParams['CHECK_PERMISSIONS'] == 'Y' )
{
	$operations = HL\HighloadBlockRightsTable::getOperationsName($hlblock_id);
	if (empty($operations))
	{
		$arResult['ERROR'] = GetMessage('HLBLOCK_VIEW_404');
	}
}

if($arParams['ROW_ID'] && $_REQUEST["addok"]=="Y"){
	$arResult['MESS']=array("TYPE"=>"OK","MESSAGE"=>"Элемент добавлен!");
}

//форма редактирования если будем создавать новый
if(!$arParams['ROW_ID'] && $arResult['ERROR'] == '' ){
	//обработчик запроса на добавление нового
	if(check_bitrix_sessid() && (!empty($_REQUEST["add_submit"]) ) && $_REQUEST['ROW_ID']=="new" && $DOSTUPADD){
		//Добавление элемента
		//echo "<pre>";print_r($_REQUEST);echo "</pre>";
		$entity = HL\HighloadBlockTable::compileEntity($hlblock);
		$fields = $USER_FIELD_MANAGER->getUserFieldsWithReadyData(
					'HLBLOCK_'.$hlblock['ID'],
					[],
					LANGUAGE_ID
				);
		$prop=$entity->getFields();
		$arResult['fields'] = $fields;
		$arResult['row'] = $prop;
		$arResult=$this->provnadostuppropnapokaz();
		$arResult=$this->provdostuppropnared($arrpardop,$arGroups,array("R","N"));

			
		$arrpropupdate=[];
		//собираем массив для создания Пустое усключаем и те поля к которым нет доступа
		foreach($arResult['row'] as $key=>$vvv){
				if($arResult['fields'][$key]["DOSTUP"]=="R" && $vvv){
					$arrpropupdate[$key]=$vvv;
				}
		}

		 //Проверяем на валидность
		 $this->arraychengeValdataprop($arrpropupdate);


         $arrpropupdate=$arResult["row"];

        //echo "<pre>";print_r($arResult['fields']);echo "</pre>";
		 //echo "<pre>";print_r($arResult["ERROR"]);echo "</pre>";
		 //echo "<pre>";print_r($arResult['fields']);echo "</pre>";

        //echo "<pre>";print_r($arResult["row"]);echo "</pre>";
		//return;
		if(count($arrpropupdate) >= 1 && $arResult['ERROR']==""){       
			$entitytable = $entity->getDataClass();
        	$result = $entitytable::add($arrpropupdate);
       		if ($result->isSuccess()) {
            	$arParams['ROW_ID']=$result->getId();
            	if($arParams['CREATELOG']=="Y"){
            		$dataprint=print_r($arrpropupdate,true);
            		$this->createLog("ADD: {$this->serviceName}", "Таблица {$hlblock['NAME']}, Добавлена запись: №{$arParams['ROW_ID']},Параметры добавления:{$dataprint} Время выполения:{$this->executeTime}");
            	}
                //Записываем первое установочное дату планирования в журнал
            	$this->updateufplandatajurnal($arParams['ROW_ID'],$hlblock_id,$arrpropupdate,array());
            	//Уведомить о создании zad

            	$arResult=[];            	
            	$sRedirectUrl=$arParams['LIST_URL'].$arParams['ROW_ID']."/?addok=Y";
            	LocalRedirect($sRedirectUrl);
        	} else {
            	$errarr=$result->getErrors();
				$strerr='';
         		foreach ($errarr as $checkError) {
         			$strerr.='<br>'.$checkError->getmessage();
         		}
         		if(strlen($strerr)>0)
         			$arResult['ERROR'].="<br>".$strerr;
        	}
        }
        else{
        	$arResult['ERROR'] .="<br>Ошибка при добавлении элеметна;";
        }


		//echo "<pre>";print_r($tmpflds);echo "</pre>";
		//echo "<pre>";print_r($arResult['fields']);echo "</pre>";
		//zad
	}
	elseif(!empty($_REQUEST["add_submit"])){
		$arResult['ERROR'] ="Не получется сохранить элемент";
	}


	//Показываем форму добавления
	if($DOSTUPADD && !$arParams['ROW_ID']){

		$entity = HL\HighloadBlockTable::compileEntity($hlblock);
		//Получаем типы полей
		$fields = $USER_FIELD_MANAGER->getUserFieldsWithReadyData(
					'HLBLOCK_'.$hlblock['ID'],
					[],
					LANGUAGE_ID
				);
		//Получаем поля
		$prop=$entity->getFields();



		$arResult['fields'] = $fields;
		$arResult['row'] = $prop;

	
		$arResult=$this->provnadostuppropnapokaz();
		$arResult=$this->provdostuppropnared($arrpardop,$arGroups,array("R","N"));
		$arResult=$this->peresort();
			
	}
	else if(!$DOSTUPADD){
		$arResult['ERROR'] ="Нет доступа к добавлению";
	}

}


if ($arResult['ERROR'] == '' && $arParams['ROW_ID'])//если ошибок нет и мы показываем тогда ищим в таблице
{
	$entity = HL\HighloadBlockTable::compileEntity($hlblock);




	if (!isset($arParams['ROW_KEY']) || trim($arParams['ROW_KEY']) == '')
	{
		$arParams['ROW_KEY'] = 'ID';
	}
	
	//Получаем типы полей
	$fields = $USER_FIELD_MANAGER->getUserFieldsWithReadyData(
					'HLBLOCK_'.$hlblock['ID'],
					[],
					LANGUAGE_ID
				);

	///Обновление записи
	if(check_bitrix_sessid() && (!empty($_REQUEST["saved_submit"]) )){
		$arrpropupdate=[];
		//получаем список полей и проверяем доступ к ним
		$prop=$entity->getFields();

		$arResult['fields'] = $fields;
		$arResult['row'] = $prop;
		//Проверяем доступ
		$tmp=$this->provnadostuppropnapokaz();
		$arrpropupdate=$tmp['row'];

		$arResult=$this->provdostuppropnared($arrpardop,$arGroups,array("R","N"));

		unset($arrpropupdate["ID"]);
		unset($arResult['row']["ID"]);
		//$this->pp($prop);
		//теперь надо проверить что меняется
		$main_query = new Entity\Query($entity);
		$main_query->setSelect(array('*'));
		$main_query->setFilter(array('='.trim($arParams['ROW_KEY']) => $arParams['ROW_ID']));

		$resulthcence = $main_query->exec();
		$resulthcence = new CDBResult($resulthcence);
		$rowcence = $resulthcence->Fetch(); //Получили текущие значения

		//echo "<pre>";print_r($arrpropupdate);echo "</pre>";
		$arResult['row']=$arrpropupdate;
		$this->arraychengeValdataprop($arrpropupdate,$rowcence,"Y");//

        $arrpropupdate=$arResult['row'];


				

		//если собрали для обновления обновляем

		if(count($arrpropupdate) >= 1 && $arResult['ERROR']==""){
					$entity_data_class = $entity->getDataClass();
					$result = $entity_data_class::update($arParams['ROW_ID'], $arrpropupdate);
					 if (!$result->isSuccess()) {
						$errarr=$result->getErrors();
						$strerr='';
         				foreach ($errarr as $checkError) {
         					$strerr.='<br>'.$checkError->getmessage();
         				}
         				if(strlen($strerr)>0)
         					$arResult['ERROR']=$strerr;
         			}
         			else{
         				if($arParams['CREATELOG']=="Y"){
         					$dataprint=print_r($arrpropupdate,true);
							$this->createLog("UPDATE: {$this->serviceName}", "Таблица {$hlblock['NAME']}, Обновлена запись: №{$arParams['ROW_ID']},Параметры обновления:{$dataprint} Время выполения:{$this->executeTime}");
						}
         				$arResult['MESS']=array("TYPE"=>"OK","MESSAGE"=>"Запись обновлена!");

						//если меняется дата фактического плана то записываем в массив
						$this->updateufplandatajurnal($arParams['ROW_ID'],$hlblock_id,$arrpropupdate,$rowcence);
						

         				//Если есть отведственный надо уведомить об измениях надо реализовать по почтовому шаблону zad

         			}
					//echo "<pre>";print_r($arResult);echo "</pre>";
		}

		
	}
	

	// А потом получаем его уже заполненый
	// row data
	$main_query = new Entity\Query($entity);
	$main_query->setSelect(array('*'));
	$main_query->setFilter(array('='.trim($arParams['ROW_KEY']) => $arParams['ROW_ID']));

	$result = $main_query->exec();
	$result = new CDBResult($result);
	$row = $result->Fetch();



//перенесено выше
	 $fields = $USER_FIELD_MANAGER->getUserFieldsWithReadyData(
	 				'HLBLOCK_'.$hlblock['ID'],
	 				$row,
	 				LANGUAGE_ID
	 			);

	if (empty($row))
	{
		$arResult['ERROR'] = GetMessage('HLBLOCK_VIEW_NO_ROW');
	}

	$arResult['fields'] = $fields;
	$arResult['row'] = $row;
	$arResult['rowall'] = $row;
	$arResult['fieldsall'] = $fields;
	//Проверяем настройки на редактирование доступа по группам пользователей

	$arResult=$this->provnadostuppropnapokaz();
	$arResult=$this->provdostuppropnared($arrpardop,$arGroups,array("R","V"));
	$arResult=$this->peresort();

	//echo "<pre>";print_r($arResult['fields']);echo "</pre>";

}


$this->IncludeComponentTemplate();