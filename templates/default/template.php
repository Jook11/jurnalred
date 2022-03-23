<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!empty($arResult['ERROR']))
{
	ShowError($arResult['ERROR']);
	//return false;
}
if (!empty($arResult['MESS']))
{
	ShowMessage($arResult['MESS']);
	//return false;
}


global $USER_FIELD_MANAGER;

//$GLOBALS['APPLICATION']->SetTitle('Highloadblock Row');

$listUrl = str_replace('#BLOCK_ID#', intval($arParams['BLOCK_ID']),	$arParams['LIST_URL']);


//echo "<pre>";print_r($arResult['row']);echo "</pre>";

?>

<a class="button m-3" href="<?=htmlspecialcharsbx($listUrl)?>"><?=GetMessage('HLBLOCK_ROW_VIEW_BACK_TO_LIST')?></a><br><br>

<div class="reports-result-list-wrap">
	<form action="" method="post" enctype="multipart/form-data">
		 <?=bitrix_sessid_post()?>
	<div class="report-table-wrap">
		<div class="reports-list-left-corner"></div>
		<div class="reports-list-right-corner"></div>
		<table cellspacing="0" class="reports-list-table table_log" id="report-result-table">
			<!-- head -->
			<tr>
				<th class="reports-first-column" style="cursor: default">
					<div class="reports-head-cell"><span class="reports-head-cell-title"><?=GetMessage('HLBLOCK_ROW_VIEW_NAME_COLUMN')?></span></div>
				</th>
				<th class="reports-last-column" style="cursor: default">
					<div class="reports-head-cell"><span class="reports-head-cell-title"><?=GetMessage('HLBLOCK_ROW_VIEW_VALUE_COLUMN')?></span></div>
				</th>
			</tr>

			<tr>
				<td class="reports-first-column">ID</td>
				<td class="reports-last-column">
					<?=$arResult['row']['ID']?>
						<?if($arResult['row']['ID']=="new"):
						echo "<input type='hidden' name='ROW_ID' value='".$arResult["row"]["ID"]."'>";
					endif;
						?>
					</td>
			</tr>

			<? foreach($arResult['fields'] as $field): ?>
				<?
				if($field['DOSTUP']=='N' || !$field['ID']):
					continue;
				endif;?>
				<? $title = $field["LIST_COLUMN_LABEL"]? $field["LIST_COLUMN_LABEL"]: $field['FIELD_NAME']; ?>
				<tr>
					<td class="reports-first-column"><?=htmlspecialcharsEx($title)?></td>
					<?
					$valign = "";
					$html = "<span class='infoinput'>".$USER_FIELD_MANAGER->getListView($field, $arResult['row'][$field['FIELD_NAME']]).'</span>';

					//$html2=$USER_FIELD_MANAGER->GetUserFields($field['FIELD_NAME'],$arResult['row'][$field['FIELD_NAME']]);
					
					?>


					<td class="reports-last-column">
					<?
					if($field['DOSTUP']=='R'){
//$field['']
						//if($field["USER_TYPE"]["USER_TYPE_ID"]=='enumeration')
							//$field["USER_TYPE"]["USER_TYPE_ID"]='enumeration2';
//echo SITE_TEMPLATE_PATH;
						//echo  $field["USER_TYPE"]["USER_TYPE_ID"];
						//echo "<pre>";print_r($field);echo "</pre>"; 
						if(!$field['ENTITY_VALUE_ID'])
							$field['ENTITY_VALUE_ID']=$field['VALUE'];

						 $APPLICATION->IncludeComponent( 
        "bitrix:system.field.edit", 
        $field["USER_TYPE"]["USER_TYPE_ID"], 
        array(
            "bVarsFromForm" => false,
            "arUserField" =>$field
        ),
        null,
        array("HIDE_ICONS"=>"Y"));
					}
					else if($field['DOSTUP']=='V'){
						echo $html;
						if($field['USER_TYPE_ID']=='employee'){
							$APPLICATION->IncludeComponent( 
        "bitrix:system.field.view", 
        $field["USER_TYPE"]["USER_TYPE_ID"], 
        array(
            "bVarsFromForm" => false,
            "arUserField" =>$field
        ),
        null,
        array("HIDE_ICONS"=>"Y"));
					}

						//echo "<pre>";print_r($field);echo "</pre>";
					}
?>
					<?//=$html?></td>
				</tr>
			<? endforeach; ?>


			<tfoot>
            <tr>
                <td colspan="2">
                	<?if($arParams['ROW_ID']):?>
                	                    <input type="submit" name="saved_submit" value="Сохранить" />
                	<?else:?>
                	                    <input type="submit" name="add_submit" value="Добавить" />
                    <?endif;?>
                </td>
            </tr>
        </tfoot>

		</table>

	</form>
	</div>
			<?if(count($arResult["rowall"]['UF_PLAN_LIST'])>0 && $arResult["rowall"]['UF_PLAN_LIST'] && $arResult["rowall"]['UF_PLAN_LIST_CHENHE']):?>

			
			<table class="reports-list-table table_log">
				<tr>
				<th  colspan="3" class="reports-first-column" style="cursor: default">
					<div class="reports-head-cell"><span class="reports-head-cell-title">Список изменений "Срок выполнения ПЛАН"</span></div>
				</th>				
			</tr>

				<tr>
				<th   class="reports-first-column" style="cursor: default">
					<div class="reports-head-cell"><span class="reports-head-cell-title">Сотрудник изменивший</span></div>
				</th>		
				<th   class="reports-first-column" style="cursor: default">
					<div class="reports-head-cell"><span class="reports-head-cell-title">Дата изменений</span></div>
				</th>	
				<th   class="reports-first-column" style="cursor: default">
					<div class="reports-head-cell"><span class="reports-head-cell-title">Установленная дата</span></div>
				</th>		
			</tr>
			<?foreach($arResult["rowall"]['UF_PLAN_LIST'] as $key=>$dat):?>
			<?$dat2=$arResult["rowall"]['UF_PLAN_LIST_CHENHE'][$key];?>
				<tr>
					<td>
			<?
			
			$arUserField=$arResult["fieldsall"]["UF_PLAN_USER_LIST"];
			echo  call_user_func_array(
				array($arUserField['USER_TYPE']['CLASS_NAME'], 'renderView'),//renderView
				array(
					$arUserField,
					array(
						'NAME' => '',
						'VALUE' => htmlspecialcharsbx($arResult["rowall"]["UF_PLAN_USER_LIST"][$key])
					)
				)
		);
		?>
					</td>
					<td><?=$dat2->toString();?></td>
					<td><?
							if($dat->getTimestamp()=="946674000"){
								echo "Дата удалена";
							}
							else{
								echo $dat->toString();
							}
							?>
				

					</td>

				</tr>
			<?endforeach;?>
			</table>
			<?
			//echo "<pre>";print_r($arResult);echo "</pre>";
			?>

		<?endif;?>
</div>