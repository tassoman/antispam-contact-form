how to create a form

# Introduction #

With this tutorial you'll be able to create a form compatible with FCC\_CUSTOM\_FORM plugin


# Create a form #

Create a new form for the plugin is a very simple task. You need to simply use in the **name** and the **id** attributes of all your input this syntax: **fcc[NAME OF THE INPUT FORM](THE.md)**

all the name surrounded by **fcc[.md](.md)** will be parsed by the plugin and sent via mail

```
<input name="fcc[thename]" id="fcc[thename]" type="text" />
<input name="fcc[email] id="fcc[email]" type="text" />
<input name="fcc[a_number]" id="fcc[a_number]" value="3" type="text" />
<input name="fcc[other_number] id="fcc[other_number]" type="text" />
<input name="fcc[date_today] id="fcc[date_today]" type="text" value="22-12-2009" />
<input type="submit" value="go!" />
```

# Validate a from #

actually you can validate a form using some hidden input validator, an invalid form will be showned with input element highlighted. The validation is server side but soon will be added also client side.

The validator are:
  1. max: max number
  1. min: min number
  1. integer: have to be a number
  1. date: check if the input is a valid date (dd-mm-aaaa)
  1. telephone: check if the input is a valid telephone number
  1. required: the elements are required
  1. email: check if it is a valid email
more validators will be written soon

ie. usage
```
<input type="hidden" name="check[integer]" value="a_number,other_number" />
<input type="hidden" name="check[date]" value="date_today" />
<input type="hidden" name="check[required]" value="a_number,email,thename" />
<input type="hidden" name="check[email]" value="email" />
<input type="hidden" name="check[max][3]" value="a_number" />
<input type="hidden" name="check[max][5]" value="other_number" />
<input type="hidden" name="check[min][2]" value="other_number" />
```

this will check that:
  * a\_number,other\_number are numbers,
  * other\_number is in [2,5]
  * a\_number is less than 3
  * email is a valid email
  * date\_today is a valid date
  * a\_number,email,thename are not null