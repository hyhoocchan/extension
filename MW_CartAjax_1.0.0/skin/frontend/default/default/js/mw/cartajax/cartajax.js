if(typeof MW == "undefined" ){
    var MW = {};
}

MW.CartAjax = {
    //init-------
    ajaxRequestString:"mw_cartajax_is_ajax",
    beforeHandler:[],
    afterHandler:[],
    addToCartUrl : null,

    //------------Add to cart from Product Detail Page---------------------
    addToCartFromPDP:function(form,callback){
        var $this = this;
        try {
            var requestData = jQuery(form).serialize()+"&"+this.ajaxRequestString+"=true";
            var request =  jQuery(form).serializeObject();
            $this.excHander("before",request);
            jQuery.post(
                form.action,
                requestData,
                function(response){
                    if(typeof (callback) == 'function'){
                        callback(response);
                    }
                    $this.excHander("after",response);
                },'json'
            );
        } catch (e) {
            console.log(e);
        }
    },
    //------------------------------------------------------------------
    addToCart:function(ob,callback){
        var $this = this;
        if($this.addToCartUrl == null){
            throw "addToCartUrl is Null";
        }

        try {
            var requestData;
            if(typeof(ob)=="object"){
                requestData = ob;
            }else{
                requestData = {
                    product : ob
                }
            }
            requestData = $this.prePareRequestData(requestData);
            $this.excHander("before",requestData);
            jQuery.post(
                $this.addToCartUrl,
                requestData,
                function(response){
                    if(typeof (callback) == 'function'){
                        callback(response);
                    }
                    $this.excHander("after",response);
                },'json'
            );
        } catch (e) {
            console.log(e);
        }
    },

    addBeforeSentEvent:function(functionName){
        if(typeof (functionName) == "function"){
            this.beforeHandler.push(functionName);
        }
    },

    addAfterSentEvent:function(functionName){
        if(typeof (functionName) == "function"){
            this.afterHandler.push(functionName);
        }
    },
    /* note: excHander is private call */
    excHander:function(type,handlerData){
        var funcs;
        switch (type){
            case "before":
                funcs = this.beforeHandler;
                break;
            case  "after":
                funcs = this.afterHandler;
                break;
        }
        try{
            for(var i=0;i<funcs.length;i++){
                var func = funcs[i];
                func(handlerData);
            }
        }catch (e){

        }

    },
    prePareRequestData:function(requestData){
        eval("requestData."+this.ajaxRequestString+"=true");
        return requestData;
    }
}
