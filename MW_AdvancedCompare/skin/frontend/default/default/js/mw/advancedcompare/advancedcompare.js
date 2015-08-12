if(typeof MW == "undefined" ){
    var MW = {};
}

MW.AdvancedCompare = {
    addToCompareUrl : null,
    comparePopupUrl : null,
    currentUrl : null,

    /*------------------ Event ----------------------------------------*/

    beforeAddHandler:[],
    addBeforeAddToCompareEvent:function(functionName,scope){
        if(typeof(scope) == "undefined") scope = "defaultcase";
        var tmp = eval("this.beforeAddHandler."+scope);
        if(typeof(tmp) == "undefined"){
            eval("this.beforeAddHandler."+scope+" = []");
        }
        if(typeof (functionName) == "function"){
            eval("this.beforeAddHandler."+scope+".push(functionName)");
        }
    },

    //---------------------------------------------------------------------
    afterAddHandler:[],
    addAfterAddToCompareEvent:function(functionName){
        if(typeof(scope) == "undefined") scope = "defaultcase";
        var tmp = eval("this.afterAddHandler."+scope);
        if(typeof(tmp) == "undefined"){
            eval("this.afterAddHandler."+scope+" = []");
        }
        if(typeof (functionName) == "function"){
            eval("this.afterAddHandler."+scope+".push(functionName)");
        }
    },

    //-------------------------------------------------------------------
    beforePopupHandler:[],
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
    //-------------------------------------------------------------------
    afterPopupHandler:[],
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
    //-------------------------------------------------------------------
    closePopupHandler:[],
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
    /* note: excHander is private call */
    excPopupHander:function(type,scope){
        if(typeof(scope) == "undefined") scope = "defaultcase";
        var funcs;
        var funcsDefault;
        try{
            switch (type){
                case "before_popup":
                    funcs = eval("this.beforePopupHandler."+scope);
                    funcsDefault = this.beforePopupHandler.defaultcase;
                    break;
                case "after_popup":
                    funcs = eval("this.afterPopupHandler."+scope);
                    funcsDefault = this.afterPopupHandler.defaultcase;
                    break;
                case "close_popup":
                    funcs = eval("this.closePopupHandler."+scope);
                    funcsDefault = this.closePopupHandler.defaultcase;
                    break;
            }
        }catch (e){
            //console.log(e);
        }
        var $thisOb=this;
        try{
            for(var i=0;i<funcs.length;i++){
                var func = funcs[i];
                func($thisOb);
            }
            if(scope != "defaultcase"){
                for(var i=0;i<funcsDefault.length;i++){
                    var func = funcsDefault[i];
                    func($thisOb);
                }
            }
        }catch (e){
            //console.log(e);
        }
    },
    //-----------------------------------------------------------------------
    excHander:function(type,handlerData,scope){
        if(typeof(scope) == "undefined") scope = "defaultcase";
        var funcs;
        var funcsDefault;
        try{
            switch (type){
                case "before_addtocompare":
                    funcs = eval("this.beforeAddHandler."+scope);
                    funcsDefault = this.beforeAddHandler.defaultcase;
                    break;
                case "after_addtocompare":
                    funcs = eval("this.afterAddHandler."+scope);
                    funcsDefault = this.afterAddHandler.defaultcase;
                    break;
            }
        }catch (e){
           // console.log(e);
        }

        try{
            for(var i=0;i<funcs.length;i++){
                var func = funcs[i];
                func(handlerData);
            }
            if(scope != "defaultcase"){
                for(var i=0;i<funcsDefault.length;i++){
                    var func = funcsDefault[i];
                    func(handlerData);
                }
            }
        }catch (e){
            //console.log(e);
        }
    },
    /*---------------------------------------------------------------*/
     /* Add to compare ajax function */
    CurrentResponse: null,
    CurrentRequest: null,
    addToCompare:function(productId,callback,scope){
        var $this = this;

        if($this.addToCompareUrl == null){
            throw "addToCompareUrl is Null";
        }
        $this.preRequestData(productId);
        $this.excHander("before_addtocompare",$this,scope);
        jQuery.post(
            $this.addToCompareUrl,
            $this.CurrentRequest,
            function(response){
                $this.CurrentResponse = response;
                if(typeof (callback) == 'function'){
                    callback(response);
                }
                $this.excHander("after_addtocompare",$this,scope);
            },'json'
        );
    },

    preRequestData:function(productId){
        if(productId == null){
            throw "productId is Null";
        }
        this.CurrentRequest = {
            product : productId,
            ajax_add_to_compare : true
        };
    },
    showPopup:function(beforeload,afterShow,closepopup,scope){
        var $this = this;
        if($this.comparePopupUrl == null){
            throw "Compare Popup Url null";
        }
        $this.currentScope = scope;
        $this.currentClose = closepopup;
        jQuery.get(
            $this.comparePopupUrl,
            function(response){
                jQuery($this.elePopupWapper).find(".content").html(response);

                $this.excPopupHander("before_popup",scope);

                if(typeof (beforeload) == 'function'){
                    beforeload($this);
                }

                jQuery($this.elePopupWapper).fadeIn("fast",function(){
                    if(typeof (afterShow) == 'function'){
                        afterShow($this);
                    }
                    $this.excPopupHander("after_popup",scope);
                });
            }
        );
    },
    currentScope:null,
    currentClose:null,
    closePopup:function(callback){
        jQuery(this.elePopupWapper).hide();
        this.excPopupHander("close_popup",this.currentScope);
        if(typeof (callback) == 'function'){
            callback(this);
        }
        if(typeof (this.currentClose) == 'function'){
            this.currentClose(this);
        }
    }
}


