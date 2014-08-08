/**
 * Forms scripts
 */

jax(document).ready(function(){
    // For form type form
    if (jax('#form-type-form')[0] != undefined) {
        var formTypeForm = jax('#form-type-form').form({
            "name" : {
                "required" : true
            }
        });

        formTypeForm.setErrorDisplay(phire.errorDisplay);
        formTypeForm.submit(function(){
            return formTypeForm.validate();
        });
    }

    if (jax('#forms-remove-form')[0] != undefined) {
        jax('#checkall').click(function(){
            if (this.checked) {
                jax('#forms-remove-form').checkAll(this.value);
            } else {
                jax('#forms-remove-form').uncheckAll(this.value);
            }
        });
        jax('#forms-remove-form').submit(function(){
            return jax('#forms-remove-form').checkValidate('checkbox', true);
        });
    }
});
