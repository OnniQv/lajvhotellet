var ids = [];
var stamps = [];
var allTimersFound = false;
var interval = null;

$(document).ready(TimeStart);

function TimeStart()
{	
	var i;	

	//if(allTimersFound)
	//	return;
		
	var count = ids.length;

	var elements = document.getElementsByName('timer');
	for(i=0;i<elements.length;i++)	
	{			
		if(elements[i].innerHTML[0] == ' ')
			continue;
			
		var stamp = parseInt(elements[i].innerHTML);
		
		ids.push(elements[i].id);
		stamps.push(stamp);
		
		allTimersFound = false;
	}	
	
	if(interval == null)
		interval = setInterval("UpdateTimers()", 60*1000);
	
	UpdateTimers();
}


function UpdateTimers()
{	
	var now = Math.round(+new Date()/1000) + (60*60); //+1 hour time zone	
		
	var i;
	for(i=0;i<ids.length;i++)	
	{	
		element = document.getElementById(ids[i]);	
		if(element == null)
		{
			//alert("can't find " + ids[i]); 
			continue;
		}
		
		var time = stamps[i];
		var date = new Date(time * 1000);
		var diff = now - time;
		var today = now - (now%(60*60*24));
		var yesterday = today - (60*60*24);
		var daybefore = yesterday - (60*60*24);
		var html = '';
		
		if(diff < 0)
			html = 'I framtiden ';
		else if(diff < 60)
			html = "Mindre än en minut sedan";
		else if(diff < 50*60)
		{
			var secs = Math.round(diff/60);
			html = "" + secs + " minut"+ (secs==1?"":"er") +  " sedan";
		}
		else if(diff < 60*60*3)
		{
			var hours = Math.round(diff/(60*60));
			html = "Ca " + hours + " timm"+ (hours==1?"e":"ar") +  " sedan";
		}
		else if(time > today)
			html = "Idag kl " + date.getHours() + ":" + date.getMinutes();
		else if(time > yesterday)
			html = "Igår kl " + date.getHours() + ":" + date.getMinutes();
		else if(time > daybefore)
			html = "I förrgår kl " + date.getHours() + ":" + date.getMinutes();
		else
			html = "" + date.getFullYear() + "-" + (date.getMonth()+1) + "-" +date.getDate() + "  " + date.getHours() + ":" + date.getMinutes();
			
		
		html = " " + html + "";
		
		element.innerHTML = html;
		
	}	
}