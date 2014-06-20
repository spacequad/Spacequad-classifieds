/*  Updates submission form fields based on changes in the category
 *  dropdown.
 */
var xmlHttp;
function ADVTtoggleEnabled(ck, id, type, base_url)
{
  if (ck.checked) {
    newval=1;
  } else {
    newval=0;
  }

  xmlHttp=ADVTgetXmlHttpObject();
  if (xmlHttp==null) {
    alert ("Browser does not support HTTP Request")
    return
  }
  var url=base_url + "/classifieds/ajax.php?action=toggleEnabled";
  url=url+"&id="+id;
  url=url+"&type="+type;
  url=url+"&newval="+newval;
  url=url+"&sid="+Math.random();
  xmlHttp.onreadystatechange=ADVTstateChanged;
  xmlHttp.open("GET",url,true);
  xmlHttp.send(null);
}

function ADVTstateChanged()
{
  var newstate;

  if (xmlHttp.readyState==4 || xmlHttp.readyState=="complete")
  {
    xmlDoc=xmlHttp.responseXML;
    id = xmlDoc.getElementsByTagName("id")[0].childNodes[0].nodeValue;
    baseurl = xmlDoc.getElementsByTagName("baseurl")[0].childNodes[0].nodeValue;
    type = xmlDoc.getElementsByTagName("type")[0].childNodes[0].nodeValue;
    if (xmlDoc.getElementsByTagName("newval")[0].childNodes[0].nodeValue == 1) {
        checked = "checked";
    } else {
        checked = "";
    }
    document.getElementById("enabled_"+id).checked = checked;
  }

}

function ADVTgetXmlHttpObject()
{
  var objXMLHttp=null
  if (window.XMLHttpRequest)
  {
    objXMLHttp=new XMLHttpRequest()
  }
  else if (window.ActiveXObject)
  {
    objXMLHttp=new ActiveXObject("Microsoft.XMLHTTP")
  }
  return objXMLHttp
}

