if(typeof MW == "undefined" ){
    var MW = {};
}

MW.SignUpPopup = {
    signUpElement : null,
    isLogin:false,
    currentScope:"defaultcase",
    //init----------------------------------------------------------------
    beforeSignUpHandler:{},
    afterSignUpHandler:{},
    closeSignUpHandler:{},
    closeSignUpCallback:null,
    //------------Add to cart from Product Detail Page---------------------
    addBeforeSignUpEvent:function(functionName,scope){
        if(typeof(scope) == "undefined") scope = "defaultcase";
        var tmp = eval("this.beforeSignUpHandler."+scope);
        if(typeof(tmp) == "undefined"){
            eval("this.beforeSignUpHandler."+scope+" = []");
        }
        if(typeof (functionName) == "function"){
            eval("this.beforeSignUpHandler."+scope+".push(functionName)");
        }
    },
    addAfterSignUpEvent:function(functionName,scope){
        if(typeof(scope) == "undefined") scope = "defaultcase";
        var tmp = eval("this.afterSignUpHandler."+scope);
        if(typeof(tmp) == "undefined"){
            eval("this.afterSignUpHandler."+scope+" = []");
        }
        if(typeof (functionName) == "function"){
            eval("this.afterSignUpHandler."+scope+".push(functionName)");
        }
    },
    addCloseSignUpEvent:function(functionName,scope){
        if(typeof(scope) == "undefined") scope = "defaultcase";
        var tmp = eval("this.closeSignUpHandler."+scope);
        if(typeof(tmp) == "undefined"){
            eval("this.closeSignUpHandler."+scope+" = []");
        }
        if(typeof (functionName) == "function"){
            eval("this.closeSignUpHandler."+scope+".push(functionName)");
        }
    },

    /* note: excHander is private call */
    excHander:function(type,handlerData,scope){
        if(typeof(scope) == "undefined") scope = "defaultcase";
        var funcs;
        switch (type){
            case "beforesignup":
                funcs = eval("this.beforeSignUpHandler."+scope);
                break;
            case "aftersignup":
                funcs = eval("this.afterSignUpHandler."+scope);
                break;
            case "closesignup":
                funcs = eval("this.closeSignUpHandler."+scope);
        }
        try{
            for(var i=0;i<funcs.length;i++){
                var func = funcs[i];
                func(handlerData);
            }
        }catch (e){

        }

    },
    /* Open popup Login */
    openSignUpPopup:function(beforeShow,afterShow,closePopup,scope){
        var $this = this;
        if(typeof(scope) == "undefined") scope = "defaultcase";
        this.currentScope = scope;
        this.closeSignUpCallback = closePopup;
        if (typeof (beforeShow) == "function") {
            beforeShow($this);
        }

        $this.excHander("beforesignup",$this,scope);
        if($this.isLogin){
            alert("is Login");
            return false;
        }
        jQuery($this.signUpElement).fadeIn("slow",function(){
            if (typeof (afterShow) == "function") {
                afterShow($this);
            }
            $this.excHander("aftersignup",$this,scope);
        });
    },
    closeSignUpPopup:function(callback){
        var $this=this;
        jQuery(this.signUpElement).fadeOut("fast",function(){
            if (typeof (callback) == "function") {
                callback($this);
            }

            if (typeof ($this.closeSignUpCallback) == "function") {
                $this.closeSignUpCallback($this);
            }

            $this.excHander("closesignup",$this,$this.currentScope);
        });
    },

    prepareRequestData : function(requestData){
        return requestData.is_mw_login_process = 1;
    }
}
