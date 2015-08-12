if(typeof MW == "undefined" ){
    var MW = {};
}

MW.LoginPopup = {
    loginElement : null,
    messageElement : null,
    loginPostUrl : null,
    currentUserLoginInfo : null,
    isLogin:false,
    currentScope:"defaultcase",
    //init----------------------------------------------------------------

    beforePopupHandler:{},
    afterPopupHandler:{},
    closePopupHandler:{},
    closePopupCallback:null,

    beforePostAjaxHandler:{},
    afterPostAjaxHandler:{},
    //------------Add to cart from Product Detail Page---------------------
    addBeforePopupEvent:function(functionName,scope){
        if(typeof(scope) == "undefined") scope = "defaultcase";
        var tmp = eval("this.beforePopupHandler."+scope);
        if(typeof(tmp) == "undefined"){
            eval("this.beforePopupHandler."+scope+" = []");
        }
        if(typeof (functionName) == "function"){
            eval("this.beforePopupHandler."+scope+".push(functionName)");
        }
    },
    addAfterPopupEvent:function(functionName,scope){
        if(typeof(scope) == "undefined") scope = "defaultcase";
        var tmp = eval("this.afterPopupHandler."+scope);
        if(typeof(tmp) == "undefined"){
            eval("this.afterPopupHandler."+scope+" = []");
        }
        if(typeof (functionName) == "function"){
            eval("this.afterPopupHandler."+scope+".push(functionName)");
        }
    },
    addClosePopupEvent:function(functionName,scope){
        if(typeof(scope) == "undefined") scope = "defaultcase";
        var tmp = eval("this.closePopupHandler."+scope);
        if(typeof(tmp) == "undefined"){
            eval("this.closePopupHandler."+scope+" = []");
        }
        if(typeof (functionName) == "function"){
            eval("this.closePopupHandler."+scope+".push(functionName)");
        }
    },
    addBeforePostEvent:function(functionName,scope){
        if(typeof(scope) == "undefined") scope = "defaultcase";
        var tmp = eval("this.beforePostAjaxHandler."+scope);
        if(typeof(tmp) == "undefined"){
            eval("this.beforePostAjaxHandler."+scope+" = []");
        }
        if(typeof (functionName) == "function"){
            eval("this.beforePostAjaxHandler."+scope+".push(functionName)");
        }
    },
    addAfterPostEvent:function(functionName,scope){
        if(typeof(scope) == "undefined") scope = "defaultcase";
        var tmp = eval("this.afterPostAjaxHandler."+scope);
        if(typeof(tmp) == "undefined"){
            eval("this.afterPostAjaxHandler."+scope+" = []");
        }
        if(typeof (functionName) == "function"){
            eval("this.afterPostAjaxHandler."+scope+".push(functionName)");
        }
    },

    /* note: excHander is private call */
    excHander:function(type,handlerData,scope){
        if(typeof(scope) == "undefined") scope = "defaultcase";
        var funcs;
        switch (type){
            case "beforepopup":
                funcs = eval("this.beforePopupHandler."+scope);
                break;
            case "afterpopup":
                funcs = eval("this.afterPopupHandler."+scope);
                break;
            case "closepopup":
                funcs = eval("this.closePopupHandler."+scope);
                break;
            case "beforepost":
                funcs = eval("this.beforePostAjaxHandler."+scope);
                break;
            case "afterpost":
                funcs = eval("this.afterPostAjaxHandler."+scope)
                break;
        }

        try{
            for(var i=0;i<funcs.length;i++){
                var func = funcs[i];
                func(handlerData);
            }
        }catch (e){

        }
        //-----------------------------------------------------------
        var defaultfuncs;

        if(scope != "defaultcase"){
            switch (type){
                case "beforepopup":
                    defaultfuncs = this.beforePopupHandler.defaultcase;
                    break;
                case "afterpopup":
                    defaultfuncs = this.afterPopupHandler.defaultcase;
                    break;
                case "closepopup":
                    defaultfuncs = this.closePopupHandler.defaultcase;
                    break;
                case "beforepost":
                    defaultfuncs = this.beforePostAjaxHandler.defaultcase;
                    break;
                case "afterpost":
                    defaultfuncs = this.afterPostAjaxHandler.defaultcase;
                    break;
            }

            try{
                for(var i=0;i<defaultfuncs.length;i++){
                    var func = defaultfuncs[i];
                    func(handlerData);
                }
            }catch (e){

            }
        }
    },
    /* Open popup Login */
    openLoginPopup:function(beforeShow,afterShow,closePopup,scope){
        var $this = this;
        if(typeof(scope) == "undefined") scope = "defaultcase";
        this.currentScope = scope;
        this.closePopupCallback = closePopup;
        if (typeof (beforeShow) == "function") {
            beforeShow($this);
        }

        $this.excHander("beforepopup",$this,scope);
        if($this.isLogin){
            $this.closeLoginPopup();
            return false;
        }
        jQuery($this.loginElement).fadeIn("slow",function(){
            if (typeof (afterShow) == "function") {
                afterShow($this);
            }
            $this.excHander("afterpopup",$this,scope);
        });
    },
    closeLoginPopup:function(callback){
        var $this=this;
        jQuery(this.loginElement).fadeOut("fast",function(){
            if (typeof (callback) == "function") {
                callback($this);
            }

            if (typeof ($this.closePopupCallback) == "function") {
                $this.closePopupCallback($this);
            }

            $this.excHander("closepopup",$this,$this.currentScope);
        });
    },
    loginPostAjax:function(requestData,beforePost,afterPost){
        var scope = this.currentScope;
        var $this = this;
        if (typeof (beforePost) == "function") {
            beforePost($this);
        }
        $this.excHander("beforepost",$this,scope);
        if($this.isLogin){
            alert("is Login");
            return false;
        }

        requestData = $this.prepareRequestData(requestData);

        if($this.loginPostUrl == null){
            throw "loginPostUrl is null";
        }
        jQuery.post(
            $this.loginPostUrl,
            requestData,
            function(response){
                $this.isLogin = response.success;
                $this.response = response;
                if (typeof (afterPost) == "function") {
                    afterPost($this);
                }
                $this.excHander("afterpost",$this,scope);
            },'json'
        );
    },
    prepareRequestData : function(requestData){
        return {
            login:requestData,
            is_mw_login_process: 1
        }
    }
}
