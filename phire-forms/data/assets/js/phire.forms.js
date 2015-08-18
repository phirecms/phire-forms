/**
 * Forms Module Scripts for Phire CMS 2
 */

jax(document).ready(function(){
    if (jax('#forms-form')[0] != undefined) {
        jax('#checkall').click(function(){
            if (this.checked) {
                jax('#forms-form').checkAll(this.value);
            } else {
                jax('#forms-form').uncheckAll(this.value);
            }
        });
        jax('#forms-form').submit(function(){
            return jax('#forms-form').checkValidate('checkbox', true);
        });
    }
});