var i=false;
$(function(){
  
  $(".remove").click(function(){
    if(confirm("Really remove?")==true){
      var $this = $(this);
      var id = $this.attr('rel');
      $.post('', {'op':"remove", 'id':id}, function(r){
        if(r=='ok') $this.parent().parent().remove();
      });
    }
    return!1;
  });
  
  
  $(".ac").click(function(){
    var $this = $(this);
    $.post('', {'ac':$this.text(), 'id':$this.attr('rel')}, function(r){
      $this.text(r);
    });
  });
  

});


