$(document).ready(function(){

   // hides the slickbox as soon as the DOM is ready
   // (a little sooner than page load)
   $('#notebox').hide();
   // shows the slickbox on clicking the noted link  
   $('a#note-show').click(function() {
      $('#notebox').slideDown('fast');
      return false;
   });
  // hides the slickbox on clicking the noted link  
   $('a#note-hide').click(function() {
      $('#notebox').hide('fast');
      return false;
   });
 
   // toggles the slickbox on clicking the noted link  
   $('a#note-toggle').click(function() {
      $('#notebox').toggle(100);
      return false;
   });
  
   notecount = 0;
   updateMsg();
   $("form#addnoteform").submit(function(){
      $.post("ajax.php",{
         note: $("#textnote").val(),
         id: $("#eventidnote").val(),
         count: notecount,
         action: "postnote"
      }, function(xml) {
         $("#textnote").empty();
         addMessages(xml);
      });
      $('#notebox').hide('fast');
      return false;
   }); 
});

function addMessages(xml) {
   if($("status",xml).text() == "2") return;
   notecount = $("count",xml).text();
   $("note",xml).each(function(id) {
      note = $("note",xml).get(id);
      
      $("#notewindow").prepend(
               "<p class=\"msg_head\">By: "+$("author",note).text()+" (" +$("time",note).text()+ ")</p>"+
		"<div class=\"msg_body\">"+
		$("text",note).text()+ 
		"</div>");
      
       //        "<h3><a href=\"#\" onclick=\"$('#"+$("seq",note).text()+ "').toggle()\">User: "+$("author",note).text()+
       //        " @ " +$("time",note).text()+ "</a></h3>"+
       //        "<div id=\""+$("seq",note).text()+"\"><p>"+$("text",note).text()+"</p></div>");
   });
   //$('.accordion').accordion('destroy');
   //   $(".accordion h3:first").addClass("active");
   //$(".accordion p:not(:first)").hide();
   //$(".accordion h3").click(function(){
      //$(this).next("p").slideToggle("fast")
      //.siblings("p:visible").slideUp("fast");
      //$(this).toggleClass("active");
      //$(this).siblings("h3").removeClass("active");
   //});
   
}
function updateMsg() {
   $.post("ajax.php",{ id: $("#eventidnote").val(), action: "getmsg" }, function(xml) {
      $("#loading").remove();
      addMessages(xml);
  	//hide the all of the element with class msg_body
	$(".msg_body").hide();
	//toggle the componenet with class msg_body
	$(".msg_head").click(function(){
		$(this).next(".msg_body").slideToggle(600);
	});
   "xml"   
   });
//   setTimeout('updateMsg()', 4000);
}
