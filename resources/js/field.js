import IndexField from './components/IndexField'
import DetailField from './components/DetailField'
import FormField from './components/FormField'

Nova.booting((app, store) => {
    app.component('IndexVideoField', IndexField);
    app.component('DetailVideoField', DetailField);
    app.component('FormVideoField', FormField);
});
