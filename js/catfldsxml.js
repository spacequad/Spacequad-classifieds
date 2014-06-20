/*  Updates submission form fields based on changes in the category
 *  dropdown.
 */
function updateCat(str, base_url)
{
  xmlHttp=GetXmlHttpObject()
  if (xmlHttp==null) {
    alert ("Browser does not support HTTP Request")
    return
  }
  var url=base_url + "/classifieds/updatecatxml.php"
  url=url+"?q="+str
  url=url+"&sid="+Math.random()
  xmlHttp.onreadystatechange=stateChanged
  xmlHttp.open("GET",url,true)
  xmlHttp.send(null)
}

function stateChanged()
{
  if (xmlHttp.readyState==4 || xmlHttp.readyState=="complete")
  {
    xmlDoc=xmlHttp.responseXML;
    if (xmlDoc.getElementsByTagName("keywords")[0].childNodes[0].nodeValue == 'none') {
      document.getElementById("keywords").value = '';
    } else {
      document.getElementById("keywords").value = 
        xmlDoc.getElementsByTagName("keywords")[0].childNodes[0].nodeValue;
    }
    if (xmlDoc.getElementsByTagName("perm_anon")[0].childNodes[0].nodeValue == 0) {
      document.getElementById("perm_anon").disabled = true;
      document.getElementById("perm_anon").checked = false;
    } else {
      document.getElementById("perm_anon").disabled = false;
      document.getElementById("perm_anon").checked = true;
    }
  }
}

function GetXmlHttpObject()
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

