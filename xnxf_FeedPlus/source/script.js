/* global sEdtLink */

$(function () {
  $("#edtAlias,#edtTag,#cmbTemplate,#edtIstopType,#edtIslock").parent().hide();
  $("#divEditTitle").append("<p><span class=\"editinputname\">链接</span> <input onmouseover=\"this.select()\" onblur=\"if(this.value=='') this.value='"+bloghost+"'\" type=\"text\" value=\"\" maxlength=\"250\" id=\"edtLink\" name=\"edtLink\" style=\"padding: 3px;max-width:520px;width: 60%;height: 1.8em;line-height: 1.8em;font-size: 1.2em;\"></p>");
  if (typeof sEdtLink == "string"){
    $("#edtLink").val(sEdtLink);
  }else{
    $("#edtLink").val(bloghost);
  }
  $("#cmbCateID option").remove();
  $("#cmbCateID").append("<option value='Feed'>Feed</option>");
  $(".divHeader2").text("Feed编辑");
  $("#edtTitle").css({maxWidth:"520px"}).unwrap();
  $("#titleheader").css({margin:0});
});
