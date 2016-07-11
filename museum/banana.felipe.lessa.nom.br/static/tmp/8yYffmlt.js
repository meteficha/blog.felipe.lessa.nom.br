$(function(){
  $("#login").hide().attr("title", "Entrar no Vamos Aprender Português!").dialog({
    autoOpen: false,
    width: 500,
    modal: true
  });
  $("#loginlink").click(function(){
    $("#login").dialog('open');
    return false;
  });
});