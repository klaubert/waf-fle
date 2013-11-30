function selectAll(x) {
   for(var i=0,l=x.form.length; i<l; i++) {
      if(x.form[i].type == 'checkbox' && x.form[i].name != 'sAll') {
         x.form[i].checked=x.form[i].checked?false:true
      }
   }
}

function unselectAll(x) {
   for(var i=0,l=x.form.length; i<l; i++) {
      if(x.form[i].type == 'checkbox' && x.form[i].name != 'sAll') {
         x.form[i].checked=false
      }
   }
}

function submitformDel() {
   document.eventsAction['action'].value = 'Delete';
   document.eventsAction.action = 'events.php';
   document.eventsAction.submit();
}

function submitformPreserve() {
   document.eventsAction['action'].value = 'Preserve';
   document.eventsAction.submit();
}

function submitformUnPreserve() {
   document.eventsAction['action'].value = 'UnPreserve';
   document.eventsAction.submit();
}

function submitformMarkFP() {
   document.eventsAction['action'].value = 'Mark';
   document.eventsAction.submit();
}
function submitformUnMarkFP() {
   document.eventsAction['action'].value = 'Unmark';
   document.eventsAction.submit();
}
