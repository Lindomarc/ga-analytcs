function showLoading() {
    $("body nav, body div.container-fluid").addClass('d-none');
    $(".loading").show()
}
showLoading()
function hideLoading() {
    $("body nav, body div.container-fluid").removeClass('d-none');
    $(".loading").fadeOut()
}


const getJSON = function (url) {
    let finished = false;
    let cancel = () => finished = true;

    return new Promise(function (resolve, reject) {
        let xhr = new XMLHttpRequest();

        xhr.open('get', url, true);
        xhr.responseType = 'json';
        xhr.onload = function () {
            let status = xhr.status;
            if (status == 200) {
                resolve(xhr.response);
            } else {
                reject(status);
            }
        };
        xhr.send();

        // When consumer calls `cancel`:
        cancel = () => {
            // In case the promise has already resolved/rejected, don't run cancel behavior!
            if (finished) {
                return;
            }

            // Cancel-path scenario
            console.log('OK, I\'ll stop counting.');
            clearInterval(id);
            reject();
        };

        // If was cancelled before promise was launched, trigger cancel logic
        if (finished) {
            // (to avoid duplication, just calling `cancel`)
            cancel();
        }
    });
};

function interval(func, times){
    let interv = function(w, t){
        return function(){
            if(typeof t === "undefined" || t-- > 0){
                setTimeout(interv, w);
                try{
                    func.call(null);
                }
                catch(e){
                    t = 0;
                    throw e.toString();
                }
            }
        };
    }(times);

    setTimeout(interv);
}

$(document).ready(function() {
    feather.replace()
});

/*
function reqRepeat (code = 0) {
    let time;
    let code, parent;

    time = new Array();
    while(code == 0) {
        code = getTandint();
    }

    return {
        execute: function(par, url, fun) {
            let data, lim;

            lim = 1500; // in miliseconds

            data = new FormData();
            data.append('data', par);

            if(xhr[code] == window.XMLHttpRequest) {
                xhr[code].abort();
            }

            xhr[code] = new XMLHttpRequest();
            xhr[code].open('POST', meurl+'url', true);
            xhr[code].onreadystatechange = function() {
                if(xhr[code].readyState == 4) {
                    time['last'] = new Date();
                    time['diff'] = time['last'].getTime()-time['begin'].getTime();

                    if(isfun(fun)) {
                        fun(xhr[code]);
                    }

                    parent = new req.repeat(code);

                    if(time['diff'] > lim) {
                        parent.execute(par, url, fun);
                    } else {
                        tim = setTimeout(function() {
                            parent.execute(par, url, fun);
                        }, lim-time['diff']);
                    }
                }
            }
            time['begin'] = new Date();
            xhr[code].send(data);
        }
    }
}

 */