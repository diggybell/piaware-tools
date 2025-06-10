var refreshTimer;

refreshTimer = setInterval(refreshContent, 60000);

function initApplication()
{
   dynamicRefresh();
}

function refreshContent()
{
   dynamicRefresh();
}

function getRestAPI(url, method, payload=null, callback=null)
{
   apiKey = 'dXNlcjpwYXNzd29yZA==';
   apiUrl = 'http://' + window.location.hostname + '/api/v1' + url;

   requestOptions =
   {
      method: method,
      headers:
      {
         'Authorization':'Basic ' + apiKey,
         'Accept':'application/json',
      },
   };
   fetch(apiUrl, requestOptions)
      .then(response =>
      {
         if (!response.ok)
         {
            throw new Error('Network response was not ok');
         }
         return response.json();
      })
      .then(data =>
      {
         callback(data);
      })
      .catch(error =>
      {
         console.error('Error: %s', error);
      });
}

function tabClicked(tabName)
{
   tabs = ['dashboard','aircraft','tracks','graphs','dod-aircraft','dod-flight', 'about'];
   
   tabs.forEach(
      function(tab)
      {
         element = document.getElementById('tab_' + tab);
         panel = document.getElementById('panel_' + tab)
         if(tabName == tab)
         {
            element.classList.add('active');
            panel.classList.add('d-block');
            panel.classList.remove('d-none');
         }
         else
         {
            element.classList.remove('active');
            panel.classList.remove('d-block');
            panel.classList.add('d-none');
         }
      }
   );

   return true;
}

function dynamicRefresh() {
   document.querySelectorAll('.pt-dynamic-refresh').forEach(function (content){
      file = content.getAttribute('pt-external-content');
      if(file != null) {
         file += '?' + Date.now();
         xhttp = new XMLHttpRequest();
         xhttp.onreadystatechange = function() {
            if (this.readyState == 4) {
               if (this.status == 200) {
                  content.innerHTML = this.responseText;
               }
               if (this.status == 404) {
                  content.innerHTML = "Page not found.";
               }
            }
         }
         xhttp.open("GET", file, true);
         xhttp.send();
      }
   });
   updatePara = document.getElementById('lastUpdated');
   if(updatePara != null) {
      date = new Date();
      updatePara.innerHTML = 'Last Update: ' + date.toLocaleDateString() + ' @ ' + date.toLocaleTimeString();
   }
}
