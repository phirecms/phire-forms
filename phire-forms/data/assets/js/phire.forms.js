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
    if (jax('#forms-manage-form')[0] != undefined) {
        jax('#checkall').click(function(){
            if (this.checked) {
                jax('#forms-manage-form').checkAll(this.value);
            } else {
                jax('#forms-manage-form').uncheckAll(this.value);
            }
        });
    }
    if (jax('#submissions-form')[0] != undefined) {
        jax('#checkall').click(function(){
            if (this.checked) {
                jax('#submissions-form').checkAll(this.value);
            } else {
                jax('#submissions-form').uncheckAll(this.value);
            }
        });
        jax('#submissions-form').submit(function(){
            return jax('#submissions-form').checkValidate('checkbox', true);
        });
    }
});