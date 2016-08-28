$(function () {
  $('#edtAlias,#edtTag,#cmbTemplate,#edtIstopType,#edtIslock').parent().fadeOut();
  $("#divEditTitle").append("<p><span class=\"editinputname\">链接:</span> <input onmouseover=\"this.select()\" onblur=\"if(this.value=='') this.value='"+bloghost+"'\" type=\"text\" value=\"\" maxlength=\"250\" id=\"edtLink\" name=\"edtLink\" style=\"width:60%;max-width:520px\"></p>");
  if (typeof sEdtLink == "string"){
    $('#edtLink').val(sEdtLink);
  }else{
    $('#edtLink').val(bloghost);
  }
  $("#cmbCateID option").remove();
  $("#cmbCateID").append("<option value='Feed'>Feed</option>");
  $(".divHeader2").text("Feed编辑");
});
