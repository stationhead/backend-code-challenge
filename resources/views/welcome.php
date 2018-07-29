<!DOCTYPE html>
<html>
    <head>
        <title>Laravel</title>

        <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">

        <style>
            html, body {
                height: 100%;
            }

            body {
                margin: 0;
                padding: 0;
                width: 100%;
                display: table;
                font-weight: 100;
                font-family: 'Lato';
            }

            .container {
                text-align: center;
                display: table-cell;
                vertical-align: middle;
            }

            .content {
                text-align: center;
                display: inline-block;
            }

            .title {
                font-size: 26px;
            }

            .ul li {
                list-style: none;
                font-size: 12px;
            }
        </style>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/1.4.5/socket.io.min.js"></script>
    </head>
    <body>
        <div class="container">
            <div class="content">
                <h1 class="current">Currently Watching activity in:<p></p></h1>
                <button class="switch">Switch Feed</button>
                <ul class="station-list">
                  <li class="station-row"> Station:
                    <span>12</span>
                    <span>Current track: </span>
                  </li>
                  <li class="station-row"> Station:
                    <span>154</span>
                    <span>Current track: </span>
                  </li>
                  <li class="station-row"> Station:
                    <span>2</span>
                    <span>Current track: </span>
                  </li>
                  <li class="station-row"> Station:
                    <span>1</span>
                    <span>Current track: </span>
                  </li>
                </ul>
                <ul class="activity-log">Station #2983462 Activity Log:</ul>
                <!-- <button class="list">Add to list</button>
                <button class="log">Add to log</button> -->
            </div>
        </div>
        <script>
            'use strict'
            console.log('loaded, yo!');
            /* INITIALIZE VARS
              ------------------------------------------------
              initialize socket on front end as 'io()'
              CONTINGENT ON REQUIRING SCRIPT IN INDEX
            */

            let ioClient  = io('192.168.56.101:3000'),
                AuthToken = Math.ceil(Math.random()*100000);

            let stationId = undefined,
                stateList = true,
                roomsList = [];

            let whereAreWe= document.querySelector('h1.current').children[0],
                button    = document.querySelector('button.switch');

                function switchRooms(){
                  if (stateList) {
                    let list = [1,2,154,12], roomName;

                    if (roomsList.indexOf('2983462_activity') != -1) {
                      let i = roomsList.indexOf('2983462_activity')
                      ioClient.emit('leave',{'roomName':roomsList[i]});
                      roomsList.splice(i,1);
                    }

                    for (var i in list) {
                      let roomName = list[i] + '_listView';
                      roomsList.push(roomName);
                      ioClient.emit('join',{'roomName': roomName});
                    }
                  }

                  if (!stateList) {
                    let roomName;
                    while (roomsList.length > 0) {
                      roomName = roomsList.pop();
                      ioClient.emit('leave',{'roomName': roomName});
                    }
                    roomName = '2983462_activity';
                    ioClient.emit('join',{'roomName': roomName});
                    roomsList.push(roomName);
                  }

                  stateList = !stateList;
                  whereAreWe.innerText = roomsList.join(', ')
                };

                ioClient.on('track', function(data){
                  let trackList = document.querySelectorAll('li.station-row')

                  for (var i = 0; i < trackList.length; i++) {
                    if (trackList[i].children[0].textContent == data.stationId) {
                      trackList[i].children[1].textContent =
                         'Current Track:' + data.trackId;
                    }
                  }
                });
                ioClient.on('activity',function(data){
                  let newLi = document.createElement('li')
                  newLi.className = data.type
                  newLi.innerText = data.handle + ':';

                  let content = data.message;
                  let message = document.createElement('span');
                      message.innerHtml = '';

                  if (data.type === 'chat') {
                      content = ' said: ' + content + '<br/>' + data.timestamp;
                  }

                  debugger
                  newLi.appendChild(message);
                  message.innerHtml += content
                  console.log(content);

                  document.querySelector('ul.activity-log').appendChild(newLi)
                });

                button.addEventListener('click', switchRooms, false)

                switchRooms();
        </script>
    </body>
</html>
