{* Вкладки *}
{capture name=tabs}
	<li {if $status===0}class="active"{/if}><a href="{url module=DealsAdmin status=0 keyword=null id=null page=null label=null}">Выйгран</a></li>
	<li {if $status==1}class="active"{/if}><a href="{url module=DealsAdmin status=1 keyword=null id=null page=null label=null}">Выкуплен</a></li>
	<li {if $status==2}class="active"{/if}><a href="{url module=DealsAdmin status=2 keyword=null id=null page=null label=null}">Оплачен</a></li>
	<li {if $status==3}class="active"{/if}><a href="{url module=DealsAdmin status=3 keyword=null id=null page=null label=null}">Возвращен</a></li>
	{if $keyword}
	<li class="active"><a href="{url module=DealsAdmin keyword=$keyword id=null label=null}">Поиск</a></li>
	{/if}

	<li class="active"><a href="{url module=DealsLabelsAdmin keyword=null id=null page=null label=null}">Метки</a></li>
{/capture}

{if $label->id}
{$meta_title = $label->name scope=parent}
{else}
{$meta_title = 'Новая метка' scope=parent}
{/if}

{* Подключаем Tiny MCE *}
{include file='tinymce_init.tpl'}

{* On document load *}
{literal}
<link rel="stylesheet" media="screen" type="text/css" href="design/js/colorpicker/css/colorpicker.css" />
<script type="text/javascript" src="design/js/colorpicker/js/colorpicker.js"></script>

<script>
$(function() {
	$('#color_icon, #color_link').ColorPicker({
		color: $('#color_input').val(),
		onShow: function (colpkr) {
			$(colpkr).fadeIn(500);
			return false;
		},
		onHide: function (colpkr) {
			$(colpkr).fadeOut(500);
			return false;
		},
		onChange: function (hsb, hex, rgb) {
			$('#color_icon').css('backgroundColor', '#' + hex);
			$('#color_input').val(hex);
			$('#color_input').ColorPickerHide();
		}
	});
});
</script>


{/literal}


{if $message_success}
<!-- Системное сообщение -->
<div class="message message_success">
	<span class="text">{if $message_success == 'added'}Метка добавлена{elseif $message_success == 'updated'}Метка обновлена{/if}</span>
	{if $smarty.get.return}
	<a class="button" href="{$smarty.get.return}">Вернуться</a>
	{/if}
</div>
<!-- Системное сообщение (The End)-->
{/if}

<!-- Основная форма -->
<form method=post id=product enctype="multipart/form-data">
	<input type=hidden name="session_id" value="{$smarty.session.id}">
	<div id="name">
		<input class="name" name="name" type="text" value="{$label->name|escape}"/> 
		<input name=id type="hidden" value="{$label->id|escape}"/> 
		<div class="checkbox">
			<span id="color_icon" style="background-color:#{$label->color};" class="order_label_big"></span>
			<input id="color_input" name="color" class="simpla_inp" type="hidden" value="{$label->color|escape}" />
		</div>
	<input class="button_green button_save" type="submit" name="" value="Сохранить" />
		
	</div> 

	
</form>
<!-- Основная форма (The End) -->

