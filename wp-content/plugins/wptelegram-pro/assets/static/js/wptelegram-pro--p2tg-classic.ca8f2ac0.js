!function(){"use strict";var e={n:function(n){var t=n&&n.__esModule?function(){return n.default}:function(){return n};return e.d(t,{a:t}),t},d:function(n,t){for(var o in t)e.o(t,o)&&!e.o(n,o)&&Object.defineProperty(n,o,{enumerable:!0,get:t[o]})},o:function(e,n){return Object.prototype.hasOwnProperty.call(e,n)}},n=window.jQuery,t=e.n(n),o="#_wptgpro_p2tg_send2tg",r='input[type="checkbox"][name="_wptgpro_p2tg_[override_switch]"]',i='[name^="_wptgpro_p2tg_[instances]"][name$="[active]"]';t()((function(){var e=t()(".wp-admin.post-php,.wp-admin.post-new-php");e.on("change",o,(function(){var n=e.find("#_wptgpro_p2tg_force_send-label");t()(this).is(":checked")?n.show():n.hide()})),e.find(o).trigger("change");var n=e.find("#wptelegram_pro_p2tg_override");n.on("change",r,(function(){var e=n.find('[name^="_wptgpro_p2tg_[instances]"]').closest(".cmb-row");t()(this).is(":checked")?(e.show(300),n.find(i).trigger("change",[!0])):e.hide(300)})),n.find(r).trigger("change"),n.on("change",i,(function(e,n){var o,r,i=null===(o=this.name)||void 0===o||null===(r=o.replace("_wptgpro_p2tg_",""))||void 0===r?void 0:r.replace(/[^0-9]/g,"");if(i){var c=n?void 0:300,p=t()('[name^="_wptgpro_p2tg_[instances]['.concat(i,']"]')).not('[name$="[active]"]').closest(".cmb-row");t()(this).is(":checked")?p.show(c):p.hide(c)}}))}))}();
//# sourceMappingURL=wptelegram-pro--p2tg-classic.ca8f2ac0.js.map