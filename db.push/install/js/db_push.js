class dbPush {
    constructor(time) {
        this.time = time? time: 5;
        if(Notification.permission === "granted"){
            setInterval(this.getNotifications, this.time * 60 * 1000);
        }else if(Notification.permission !== "denied"){
            Notification.requestPermission((permission) => {
                if(!('permission' in Notification)){
                    Notification.permission = permission;
                }
                if(permission === 'granted'){
                    setInterval(this.getNotifications, this.time * 60 * 1000);
                }
            })
        }
    }
    getNotifications(){
        let xhr = new XMLHttpRequest();
        xhr.open("GET", "/ajax/db/push/db.push.php?getNotification=true");
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.send();
        xhr.onreadystatechange=()=>{
            if(xhr.readyState === 4){
                if(xhr.status == 200 && xhr.status<300){
                    var notificationsArr = JSON.parse(xhr.response);
                    notificationsArr.forEach((element, index) => {
                        setTimeout(() => {
                            let icon_path = "https://" + document.domain + element.icon;
                            new Notification(element.authorName, {
                                body: element.message,
                                icon: icon_path,
                            });
                        }, 3000 * (index + 1));
                    });
                }
            }
        };
    }
  }
document.addEventListener('DOMContentLoaded', function(){
    new dbPush();
})