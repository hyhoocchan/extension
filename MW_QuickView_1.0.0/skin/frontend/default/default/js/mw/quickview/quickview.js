if (typeof MW == "undefined") {
    var MW = {};
}

MW.QuickView = {
    product_id : null,
    //-------- init -------
    quickViewUrl:null,
    eleWapper:null,
    eleImageLoading:null,

    beforeQuickViewHander:[],
    afterQuickViewHander:[],
    closeQuickViewHander:[],

    productOptionUrl:null,
    productOptionWapper:null,
    productOptionImageLoading:null,

    beforeProductOptionHander:[],
    afterProductOptionHander:[],
    closeProductOptionHander:[],

    closeCallBackQuickViewCurrent:function () {
    },
    closeCallBackProductOptionCurrent:function () {
    },
    //---------------------------
    addBeforeProductOptionEvent:function (functionName) {
        if (typeof (functionName) == "function") {
            this.beforeProductOptionHander.push(functionName);
        }
    },
    addAfterProductOptionEvent:function (functionName) {
        if (typeof (functionName) == "function") {
            this.afterProductOptionHander.push(functionName);
        }
    },
    addCloseProductOptionEvent:function (functionName) {
        if (typeof (functionName) == "function") {
            this.closeProductOptionHander.push(functionName);
        }
    },
    //----------------------------------
    //---------------------------
    addBeforeQuickViewEvent:function (functionName) {
        if (typeof (functionName) == "function") {
            this.beforeQuickViewHander.push(functionName);
        }
    },
    addAfterQuickViewEvent:function (functionName) {
        if (typeof (functionName) == "function") {
            this.afterQuickViewHander.push(functionName);
        }
    },
    addCloseQuickViewEvent:function (functionName) {
        if (typeof (functionName) == "function") {
            this.closeQuickViewHander.push(functionName);
        }
    },
    //----------------------------------
    openQuickView:function (proId, beforeViewCallBack, afterViewCallBack, closeCallBack) {
        var $this = this;
        $this.product_id = proId;

        $this.closeCallBackQuickViewCurrent = closeCallBack;
        $this.excHander("before", $this);
        if (typeof (beforeViewCallBack) == 'function') {
            beforeViewCallBack($this);
        }
        if ($this.quickViewUrl == null) {
            throw "openPopupUrl is NULL";
        }

        var IFrame = jQuery($this.eleWapper).find("iframe");
        jQuery($this.eleImageLoading).show();
        jQuery($this.eleWapper).fadeIn("slow");
        IFrame.src($this.quickViewUrl + "?id=" + proId, function () {
            jQuery($this.eleImageLoading).hide();
            $this.excHander("after", $this);
            if (typeof (afterViewCallBack) == 'function') {
                afterViewCallBack($this);
            }
        });
    },
    openProductOption:function (proId, beforeViewCallBack, afterViewCallBack, closeCallBack) {
        var $this = this;
        $this.product_id = proId;
        $this.closeCallBackProductOptionCurrent = closeCallBack;
        $this.excHander("before", $this, true);
        if (typeof (beforeViewCallBack) == 'function') {
            beforeViewCallBack($this);
        }
        if ($this.productOptionUrl == null) {
            throw "productOptionUrl is NULL";
        }

        var IFrame = jQuery($this.productOptionWapper).find("iframe");
        jQuery($this.productOptionImageLoading).show();
        jQuery($this.productOptionWapper).fadeIn("slow");
        IFrame.src($this.productOptionUrl + "?id=" + proId, function () {
            jQuery($this.productOptionImageLoading).hide();
            $this.excHander("after", $this, true);
            if (typeof (afterViewCallBack) == 'function') {
                afterViewCallBack($this);
            }
        });
    },

    closeProductOption:function () {
        var $this = this;
        jQuery($this.productOptionWapper).fadeOut("slow", function () {
            $this.excHander("close", $this, true);
            if (typeof ($this.closeCallBackProductOptionCurrent) == 'function') {
                $this.closeCallBackProductOptionCurrent($this);
            }
            $this.closeCallBackProductOptionCurrent = null;
        });
    },

    closeQuickView:function () {
        var $this = this;
        jQuery($this.eleWapper).fadeOut("slow", function () {
            $this.excHander("close", $this);
            if (typeof ($this.closeCallBackQuickViewCurrent) == 'function') {
                $this.closeCallBackQuickViewCurrent($this);
            }
            $this.closeCallBackQuickViewCurrent = null;
        });
    },
    /* note: excHander is private call */
    excHander:function (type, handlerData, productOptionType) {
        var funcs;

        switch (type) {
            case "before":
                funcs = productOptionType == true ? this.beforeProductOptionHander : this.beforeQuickViewHander;
                break;
            case  "after":
                funcs = productOptionType == true ? this.afterProductOptionHander : this.afterQuickViewHander;
                break;
            case  "close":
                funcs = productOptionType == true ? this.closeProductOptionHander : this.closeQuickViewHander;
                break;
        }

        try {
            for (var i = 0; i < funcs.length; i++) {
                var func = funcs[i];
                func(handlerData);
            }
        } catch (e) {

        }
    }
}
jQuery.getDocHeight = function(){
		 var D = document;
		 return Math.max(Math.max(D.body.scrollHeight,    D.documentElement.scrollHeight), Math.max(D.body.offsetHeight, D.documentElement.offsetHeight), Math.max(D.body.clientHeight, D.documentElement.clientHeight));
};