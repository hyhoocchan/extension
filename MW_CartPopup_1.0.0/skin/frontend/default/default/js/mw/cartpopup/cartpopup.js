if(typeof MW == "undefined" ){
    var MW = {};
}

MW.CartPopup = {
    //init-------
    ajaxRequestKey:"mw_cartpopup_is_ajax",

    beforeGetPopupHandler:[],
    beforeShowHandler:[],
    afterShowHandler:[],
    closePopupHandler:[],

    cartPopupUrl:null,
    elePopupWapper:null,
    contentContainer:null,
    //--------------------------------
    showCartPopup:function(beforeGetPopup,beforeShow,afterShow){
        try {
            var $this = this;
            $this.excHander("beforegetpopup",$this);

            if(typeof (beforeGetPopup) == 'function'){
                beforeGetPopup($this);
            }

            var requestData = {};
            eval("requestData."+$this.ajaxRequestKey+"=true");
            jQuery.get(
              $this.cartPopupUrl,
              requestData,
              function(data){
                  jQuery($this.elePopupWapper).find($this.contentContainer).html(data);

                  $this.excHander("beforeshow",$this);
                  if(typeof (beforeShow) == 'function'){
                      beforeShow($this);
                  }

                  jQuery($this.elePopupWapper).fadeIn("fast",function(){
                      if(typeof (afterShow) == 'function'){
                          afterShow($this);
                      }
                      $this.excHander("aftershow",$this);
                  });
              }
            );
        } catch (e) {
            console.log(e);
        }
    },
    addBeforeGetPopupEvent:function(functionName){
        if(typeof (functionName) == "function"){
            this.beforeGetPopupHandler.push(functionName);
        }
    },
    addBeforeShowEvent:function(functionName){
        if(typeof (functionName) == "function"){
            this.beforeShowHandler.push(functionName);
        }
    },
    addAfterShowEvent:function(functionName){
        if(typeof (functionName) == "function"){
            this.afterShowHandler.push(functionName);
        }
    },
    addClosePopupEvent:function(functionName){
        if(typeof (functionName) == "function"){
            this.closePopupHandler.push(functionName);
        }
    },
    //------------------------------------------------------------------

    closePopup : function(callback){
        jQuery(this.elePopupWapper).hide();
        this.excHander("closepopup",{});
        if(typeof (callback) == 'function'){
            callback();
        }
    } ,
    /* note: excHander is private call */
    excHander:function(type,handlerData){
        var funcs;
        switch (type){
            case "beforegetpopup":
                funcs = this.beforeGetPopupHandler;
                break;
            case  "beforeshow":
                funcs = this.beforeShowHandler;
                break;
            case  "aftershow":
                funcs = this.afterShowHandler
                break;
            case  "closepopup":
                funcs = this.closePopupHandler;
                break;
        }

        try{
            for(var i=0;i<funcs.length;i++){
                var func = funcs[i];
                func(handlerData);
            }
        }catch (e){

        }
    }
}