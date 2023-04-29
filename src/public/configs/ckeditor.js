// CkEditor Script
function createCkEditor(selector, uploadPath) {
    let csrf_token = $('[name="csrf-token"]').attr("content");
    if (!csrf_token) {
      console.error("Meta tag csrf-token not found!");
      return;
    }
  
    ClassicEditor.create(selector, {
      // https://ckeditor.com/docs/ckeditor5/latest/features/toolbar/toolbar.html#extended-toolbar-configuration-format
      /*toolbar: {
              items: [
                  'heading', '|',
                  'bold', 'italic', 'removeFormat', '|',
                  'bulletedList', 'numberedList', '|',
                  'outdent', 'indent', '|',
                  
                  'alignment', '|',
                  'link', 'insertImage', 'blockQuote', 'insertTable', 'horizontalLine', '|',
                  'undo', 'redo', 'sourceEditing'
              ],
              //shouldNotGroupWhenFull: true
          },*/
      // https://ckeditor.com/docs/ckeditor5/latest/features/headings.html#configuration
      heading: {
        options: [
          {
            model: "paragraph",
            title: "Paragraph",
            class: "ck-heading_paragraph",
          },
          {
            model: "heading1",
            view: "h1",
            title: "Heading 1",
            class: "ck-heading_heading1",
          },
          {
            model: "heading2",
            view: "h2",
            title: "Heading 2",
            class: "ck-heading_heading2",
          },
          {
            model: "heading3",
            view: "h3",
            title: "Heading 3",
            class: "ck-heading_heading3",
          },
          {
            model: "heading4",
            view: "h4",
            title: "Heading 4",
            class: "ck-heading_heading4",
          },
          {
            model: "heading5",
            view: "h5",
            title: "Heading 5",
            class: "ck-heading_heading5",
          },
          {
            model: "heading6",
            view: "h6",
            title: "Heading 6",
            class: "ck-heading_heading6",
          },
        ],
      },
      // Be careful with the setting below. It instructs CKEditor to accept ALL HTML markup.
      // https://ckeditor.com/docs/ckeditor5/latest/features/general-html-support.html#enabling-all-html-features
      htmlSupport: {
        allow: [
          {
            name: /.*/,
            attributes: true,
            classes: true,
            styles: true,
          },
        ],
      },
      // https://ckeditor.com/docs/ckeditor5/latest/features/link.html#custom-link-attributes-decorators
      link: {
        decorators: {
          addTargetToExternalLinks: true,
          defaultProtocol: "https://",
          toggleDownloadable: {
            mode: "manual",
            label: "Downloadable",
            attributes: {
              download: "file",
            },
          },
        },
      },
      // https://ckeditor.com/docs/ckeditor5/latest/features/mentions.html#configuration
      mention: {
        feeds: [
          {
            marker: "@",
            feed: [
              "@apple",
              "@bears",
              "@brownie",
              "@cake",
              "@cake",
              "@candy",
              "@canes",
              "@chocolate",
              "@cookie",
              "@cotton",
              "@cream",
              "@cupcake",
              "@danish",
              "@donut",
              "@dragée",
              "@fruitcake",
              "@gingerbread",
              "@gummi",
              "@ice",
              "@jelly-o",
              "@liquorice",
              "@macaroon",
              "@marzipan",
              "@oat",
              "@pie",
              "@plum",
              "@pudding",
              "@sesame",
              "@snaps",
              "@soufflé",
              "@sugar",
              "@sweet",
              "@topping",
              "@wafer",
            ],
            minimumCharacters: 1,
          },
        ],
      },
      image: {
        styles: ["alignCenter", "alignLeft", "alignRight"],
        resizeOptions: [
          {
            name: "resizeImage:original",
            label: "Original",
            value: null,
          },
          {
            name: "resizeImage:50",
            label: "50%",
            value: "50",
          },
          {
            name: "resizeImage:75",
            label: "75%",
            value: "75",
          },
        ],
        toolbar: [
          "imageTextAlternative",
          "|", //, 'toggleImageCaption'
          "imageStyle:inline",
          "imageStyle:wrapText",
          "imageStyle:breakText",
          "imageStyle:side",
          "|",
          //'resizeImage'
        ],
        insert: {
          integrations: ["insertImageViaUrl"],
        },
      },
      simpleUpload: {
        // The URL that the images are uploaded to.
        uploadUrl: uploadPath,
  
        // Enable the XMLHttpRequest.withCredentials property.
        withCredentials: true,
  
        // Headers sent along with the XMLHttpRequest to the upload server.
        headers: {
          "X-CSRF-TOKEN": csrf_token,
        },
      },
      allowedContent: true,
    })
      .then((editor) => {
        window.editor = editor;
        // Prevent showing a warning notification when user is pasting a content from MS Word or Google Docs.
        window.preventPasteFromOfficeNotification = true;
  
        //document.querySelector( '.ck.ck-editor__main' ).appendChild( editor.plugins.get( 'WordCount' ).wordCountContainer );
      })
      .catch((error) => {
        console.error("Oops, something went wrong!");
        console.error(
          "Please, report the following error on https://github.com/ckeditor/ckeditor5/issues with the build id and the error stack trace:"
        );
        console.warn("Build id: 513xdav0owiz-cb6an6tupu7e");
        console.error(error);
      });
  }
  