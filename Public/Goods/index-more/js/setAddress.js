function renderAddress(){
    var s=`<option value="">please select</option>`;
    for(k in address){
        s+=`<option value="`+k+`">`+k+`</option>`;
    }
    return s;
}
function renderTwoLevel(s){
    var t=`<option value="">please select</option>`;
    for(k in address[s]){
        t+=`<option value="`+k+`">`+k+`</option>`;
    }
    return t;
}
function renderThreeLevel(m,n){
    var x=`<option value="">please select</option>`;
    for(k in address[m][n]){
        x+=`<option value="`+k+`">`+k+`</option>`;
    }
    return x;
}
function renderForuLevel(m,n,p){
    var y=`<option value="">please select</option>`;
    for(k in address[m][n][p]){
        y+=`<option value="`+k+`">`+k+`</option>`;
    }
    return y;
}
function renderCode(m,n,p,q){
    return address[m][n][p][q]["code"].toString().length==5?address[m][n][p][q]["code"]:("0"+address[m][n][p][q]["code"]);
}