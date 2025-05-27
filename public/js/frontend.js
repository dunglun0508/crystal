/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./resources/js/frontend.js":
/*!**********************************!*\
  !*** ./resources/js/frontend.js ***!
  \**********************************/
/***/ (function() {

(function (global) {
  var Frontend = function Frontend() {
    this.initialize();
  };
  Frontend.prototype = {
    initialize: function initialize() {
      var that = this;
      that.scrollAbout();
      // that.checkingFileDownloadReady()
      // that.checkFreeDownload()
      // that.createCountDown()
      // that.ezplus();
      // that.increaseView()
      // that.validateForm()
      // that.clickEditProfile()
      // that.clickBuyProduct()
      // that.clickAddProduct()
      // that.clickFavourite()
      // that.colorFaheart()
      // that.chooseCollectionSearch()
      // that.clickToButton()
      // that.swiperSlider()
      // that.clickBtn_loading()
      // that.clickRemoveProduct()
      // that.clickClearProduct()
      // that.clickCheckAll()
      // that.getSreenHeight()
      // that.clickTurnOnModal()
      // that.clickCopyClipboard();
      // // that.addEventDatetimePicker();
      // that.addAutoCompleteBankInput();
      // that.editBank();
      // that.addProgressBarForPaypalButton();
      // that.addEventOpenModalPayment();
      // that.clickPaymentBtn();
      // that.clickCollection();
      // that.hoverIconCart();
      // $('[data-toggle="tooltip"]').tooltip();
      // if(checkout){
      //     $('#selectPaymentGateWay').modal('show');
      // }
      //  var swiper3 = new Swiper('.swiper-3-slider', {
      //     slidesPerView: 4,
      //     spaceBetween: 20,
      //  navigation: {
      //         nextEl: '.row-best-seller .swiper-button-next',
      //         prevEl: '.row-best-seller .swiper-button-prev',
      //     },
      //     breakpoints: {
      //         320: {
      //           slidesPerView: 1,
      //           spaceBetween: 10
      //       },
      //       760: {
      //           slidesPerView: 2,
      //           spaceBetween: 10
      //       },
      //       1140: {
      //           slidesPerView: 4,
      //           spaceBetween: 20
      //       }
      //   }
      // });
      //   var swiper1 = new Swiper('.swiper-1-slider', {
      //     slidesPerView: 4,
      //     spaceBetween: 20,
      //  navigation: {
      //         nextEl: '.row-new-update .swiper-button-next',
      //         prevEl: '.row-new-update .swiper-button-prev',
      //     },
      //     breakpoints: {
      //         320: {
      //           slidesPerView: 1,
      //           spaceBetween: 10
      //       },
      //       760: {
      //           slidesPerView: 2,
      //           spaceBetween: 10
      //       },
      //       1140: {
      //           slidesPerView: 4,
      //           spaceBetween: 20
      //       }
      //   }
      // });
    },
    scrollAbout: function scrollAbout() {
      var that = this;
      if ($('#about').length == 0) {
        return false;
      }
      var offsetTopAbout = $('#about').offset().top;
      var critOffset = offsetTopAbout * 2 / 3;
      var isChangeAnimate = false;
      $(window).on("scroll", function () {
        var scrollTop = $(window).scrollTop();
        if (scrollTop > critOffset) {
          if (!isChangeAnimate) {
            $("#about .img-leaf-2").animate({
              top: '400px',
              left: "200px",
              opacity: 1
            }, 1000);
            $("#about .img-leaf-1").animate({
              top: '500px',
              left: "500px",
              opacity: 1
            }, 1000);
            $("#about .img-leaf-3").animate({
              right: "-300px",
              opacity: 1
            }, 1000);
            isChangeAnimate = true;
          }
        } else {
          var widthContainer = $('#about .container').width();
          if (isChangeAnimate) {
            $("#about .img-leaf-2").animate({
              top: '-87px',
              left: "-44px",
              opacity: 0.5
            }, 1000);
            $("#about .img-leaf-1").animate({
              top: '150px',
              left: widthContainer / 2 - 100 + 'px',
              opacity: 0.5
            }, 1000);
            $("#about .img-leaf-3").animate({
              right: "-512px",
              opacity: 0.5
            }, 1000);
            isChangeAnimate = false;
          }
        }
      });
    },
    clickCollection: function clickCollection() {
      var that = this;
      $('.collection-item').on('click', function (e) {
        var dataHref = $(e.currentTarget).attr('data-href');
        if (typeof dataHref != 'undefined') {
          window.location.href = dataHref;
        }
      });
    },
    hoverIconCart: function hoverIconCart() {
      var that = this;
      var isShowCart = false;
      $('body').on('click', function () {
        if (!isShowCart) {
          $('.cart-panel-content').hide();
        }
      });
      $('.icon-add-to-cart ').click(function (e) {
        e.preventDefault();
        isShowCart = true;
        $('.cart-panel-content').show();
        setTimeout(function () {
          isShowCart = false;
        }, 100);
      });
      $('.btn-goto-cart').on('click', function (e) {
        e.preventDefault();
        var dataHref = $(e.currentTarget).attr('data-href');
        window.location.href = dataHref;
      });

      // $('.cart-panel-content').mouseout(function(e){
      //     $(e.currentTarget).hide();
      // });
      // $('.cart-panel-content').mouseenter(function(e){
      //     setTimeout(function(){
      //         isShowCart = true;
      //     },0);
      // });
      // $('.icon-add-to-cart ').mouseout(function(e){
      //     isShowCart = false;
      //     setTimeout(function(){
      //         if(!isShowCart){
      //             $('.cart-panel-content').hide();
      //         }
      //     },200);
      // });
    },
    addEventOpenModalPayment: function addEventOpenModalPayment() {
      $('.btn-select-payment').click(function (e) {
        $('#cartCheckoutLogin').modal('hide');
        $('#selectPaymentGateWay').modal('show');
      });
    },
    clickPaymentBtn: function clickPaymentBtn() {
      var that = this;
      $('.btn-payment').on('click', function (e) {
        var dataHref = $(e.currentTarget).attr('data-href');
        if (typeof dataHref == 'undefined') {
          return false;
        }
        window.location.href = dataHref;
      });
    },
    checkingFileDownloadReady: function checkingFileDownloadReady() {
      if ($('input[name="order_code"]').length > 0) {
        var orderCode = $('input[name="order_code"]').val();
        var appUrl = $('input[name="app_url"]').val();
        var myInterval = setInterval(function () {
          jQuery.get('/cart/checking/' + orderCode, function (response) {
            response = JSON.parse(response);
            if (response.success) {
              clearInterval(myInterval);
              window.location.href = appUrl + "cart/filedownload/" + orderCode;
            }
          });
        }, 3000);
      }
    },
    addProgressBarForPaypalButton: function addProgressBarForPaypalButton() {
      var that = this;
      $('#checkoutWithoutLoginPayPal').on('click', function (e) {
        $(e.currentTarget).find('.buttonload').show();
      });
    },
    editBank: function editBank() {
      $('#btn-edit').on('click', function (e) {
        e.preventDefault();
        $('#form-bank .form-group input').prop("disabled", false);
        $('#btn-submit').prop("disabled", false);
        $('#btn-submit').html('Sửa thông tin');
        $(this).prop("disabled", true);
      });
    },
    addAutoCompleteBankInput: function addAutoCompleteBankInput() {
      listBank = ['Ngân hàng An Bình - ABBank', 'Ngân hàng Á Châu - ACB', 'Ngân hàng NN & PTNT VN - Agribank, VBARD', 'Ngân hàng ANZ Việt Nam - ANZ', 'BANGKOK  BANK - BANGKOK  BANK', 'Công ty cổ phần chuyển mạch tài chính quốc gia Việt Nam - Banknetvn', 'Ngân hàng TMCP Bảo Việt - Baoviet Bank', 'BANK OF CHINA - BC', 'NH ĐT&PT Campuchia CN HCM - BIDC HCM', 'NH ĐT&PT Campuchia CN Hà Nội - BIDC HN', 'Ngân hàng Đầu tư và Phát triển Việt Nam - BIDV', 'BNP Paribas Bank HCM - BNP Paribas HCM', 'Ngan hang BNP Paribas CN Ha Noi - BNP Paribas HN', 'Bank of Communications - BOC', 'Ngân hàng BPCEIOM CN  TP Hồ Chí Minh - BPCEICOM', 'BANK OF TOKYO - MITSUBISHI UFJ - TP HCM - BTMU HCM', 'BANK OF TOKYO - MITSUBISHI UFJ - HN - BTMU HN', 'Credit Agricole Corporate and Investment Bank - CACIB', 'Commonwealth Bank of Australia - CBA', 'China Construction Bank Corporation - CCBC', 'The Chase Manhattan Bank - CHASE', 'Ngân hàng TNHH MTV CIMB Việt Nam - CIMB', 'Citi Bank TP HCM - CitibankHCM', 'Citi Bank Ha Noi - CitibankHN', 'Ngân hàng Hợp tác Việt Nam - COOPBANK', 'Ngân hàng CTBC CN TP Hồ Chí Minh - CTBC', 'Ngân hàng Cathay - CTU', 'DEUTSCHE BANK - DB', 'DBS Bank Ltd - DBS', 'Ngân hàng Đông Á - Dong A Bank, DAB', 'Ngân hàng Xuất nhập khẩu Việt Nam - Eximbank, EIB', 'First Commercial Bank - FCNB', 'First Commercial Bank Ha Noi - FCNB HN', 'Ngân hàng Dầu khí Toàn cầu - GP Bank', 'Ngân hàng Phát triển TP HCM - HDBank', 'Ngân hàng Hong Leong Viet Nam - HLO', 'Hua Nan Commercial Bank - HNCB', 'NH TNHH Một Thành Viên HSBC Việt Nam - HSBC', 'Ngân hàng The Hongkong và Thượng Hải - HSBC HN', 'Industrial Bank of Korea - IBK', 'ICB of China CN Ha Noi - ICB', 'Indovina Bank - IVB', 'Kho Bạc Nhà Nước - KBNN', 'Korea Exchange Bank - KEB', 'Ngân hàng Kiên Long - Kienlongbank', 'Ngân hàng Kookmin - KMB', 'Ngan hàng TMCP Bưu điện Liên Việt - Lienvietbank,  LPB', 'Ngân hàng Hàng Hải Việt Nam - Maritime Bank, MSB', 'Malayan Banking Berhad - Maybank', 'Ngân hàng Quân Đội - MB', 'Malayan Banking Berhad - MBB', 'Mizuho Corporate Bank - TP HCM - MCB_HCM', 'Mega ICBC Bank - MICB', 'Mizuho Corporate Bank - Mizuho Bank', 'Ngân hàng Nam Á - Nam A Bank, NAB', 'Ngân hàng Bắc Á - NASBank, NASB', 'Ngân hàng Quoc Dan - NCB', 'Oversea - Chinese Bank - OCBC', 'Ngân hàng Đại Dương - Ocean Bank', 'Ngân hàng Phương Đông - Oricombank, OCB, PhuongDong Bank', 'Ngân hàng Xăng dầu Petrolimex - PG Bank', 'NH TMCP Đại Chúng Viet Nam - PVcombank', 'Quỹ tín dụng cơ sở - QTDCS', 'Ngân hàng Sài Gòn Thương Tín - Sacombank', 'Ngân hàng Sài Gòn Công Thương - Saigonbank', 'Ngân Hàng Nhà Nước - SBV', 'Ngân hàng TMCP Sài Gòn - SCB', 'Ngân hàng Standard Chartered Bank Việt Nam - SCBank', 'Ngân hàng Standard Chartered Bank HN - SCBank HN', 'The Shanghai Commercial & Savings Bank CN Đồng Nai - SCSB', 'Ngân hàng TMCP Đông Nam Á - SeABank', 'Ngân hàng Sài Gòn - Hà Nội - SHB', 'Ngân hàng TNHH MTV Shinhan Việt Nam - Shinhan Bank', 'Ngân hàng The Siam Commercial Public - SIAM', 'Sumitomo Mitsui Banking Corporation HCM - SMBC', 'Sumitomo Mitsui Banking Corporation HN - SMBC HN', 'Ngân hàng SinoPac - SPB', 'Ngân hàng Kỹ thương Việt Nam - Techcombank, TCB', 'Taipei Fubon Commercial Bank Ha Noi - TFCBHN', 'Taipei Fubon Commercial Bank TP Ho Chi Minh - TFCBTPHCM', 'United Oversea Bank - UOB', 'Ngân hàng Chính sách xã hội Việt Nam - VBSP', 'Ngân hàng Phát triển Việt Nam - VDB', 'Ngân hàng Quốc tế - VIBank, VIB', 'Ngân hàng VID Public - VID public', 'Ngân hàng Việt Hoa - Viet Hoa Bank', 'Ngân hàng Việt Á - VietA Bank, VAB', 'Ngân hàng Việt Nam Thương Tín - Vietbank', 'NHTMCP Bản Việt - VietCapital Bank', 'Ngân hàng TMCP Ngoại Thương - Vietcombank, VCB', 'Ngân hàng công thương Việt Nam - Vietinbank', 'NH TMCP Xây dựng Việt Nam - VNCB', 'Ngân hàng Thương mại cổ phần Việt Nam Thịnh Vượng - VPBank', 'Ngân hàng Liên doanh Việt Nga - VRB', 'Ngân hàng Vũng Tàu - Vung Tau', 'NH Woori HCM - WHHCM', 'WOORI BANK Hà Nội - WHHN'];
      $("#inputBank").autocomplete({
        source: listBank
      });
    },
    addEventDatetimePicker: function addEventDatetimePicker() {
      $('#txtTime').daterangepicker({
        opens: 'left',
        autoUpdateInput: false,
        locale: {
          cancelLabel: 'Clear'
        }
      }, function (start, end, label) {
        $('#txtTime').val(start.format('YYYY-MM-DD') + ',' + end.format('YYYY-MM-DD'));
      });
    },
    clickCopyClipboard: function clickCopyClipboard() {
      $('.label-link').on('click', function (e) {
        var linkAffiliate = $(this).attr('data-link-affiliate');
        navigator.clipboard.writeText(linkAffiliate);
        $.growl({
          title: "Đã copy link affiliate!",
          message: ''
        });
        // $.growl.notice({message: "Copied the text: " + linkAffiliate});
      });
    },
    checkFreeDownload: function checkFreeDownload() {
      $('.btn-buy-download').off('click').on('click', function () {
        $('.loadingio-spinner-spinner-ry4x170fish').show();
        var productID = $(this).attr('data-id');
        var target = $(this);
        $.ajax({
          url: $(this).attr('data-url'),
          type: "get",
          success: function success(result) {
            $('.loadingio-spinner-spinner-ry4x170fish').hide();
            result = JSON.parse(result);
            //                        if (result.is_favourite) {
            //                            target.css("color", "rgb(239, 33, 128)");
            //                        } else {
            //                            target.css("color", "#8d8989");
            //                            if ($(".info_product_favorite").length > 0) {
            //                                target.closest(".info_product_favorite").remove();
            //                            }
            //                        }
            if (result.login) {
              location.href = '/cart/freedownload/' + productID;
            } else {
              $.growl.warning({
                message: "Bạn chưa đăng nhập!"
              });
              $('#exampleModal23').modal('show');
            }
          }
        });
      });
    },
    createCountDown: function createCountDown() {
      $('.countdown_time').each(function (index, item) {
        var alias = "Khuyến mại sau: ";
        if ($(item).hasClass('countdown_to_end')) {
          var alias = "Khuyến mại còn: ";
        }
        // Set the date we're counting down to
        var countDownDate = parseInt($(item).find('input').val()) * 1000;

        // Update the count down every 1 second
        var x = setInterval(function () {
          // Get today's date and time
          var now = new Date().getTime();

          // Find the distance between now and the count down date
          var distance = countDownDate - now;

          // Time calculations for days, hours, minutes and seconds
          var days = Math.floor(distance / (1000 * 60 * 60 * 24));
          var hours = Math.floor(distance % (1000 * 60 * 60 * 24) / (1000 * 60 * 60));
          var minutes = Math.floor(distance % (1000 * 60 * 60) / (1000 * 60));
          var seconds = Math.floor(distance % (1000 * 60) / 1000);

          // Display the result in the element with id="demo"
          if (days > 0) {
            $(item).find('span').html(alias + days + "d " + hours + "h " + minutes + "m " + seconds + "s ");
          } else if (hours > 0) {
            $(item).find('span').html(alias + hours + "h " + minutes + "m " + seconds + "s ");
          } else if (minutes > 0) {
            $(item).find('span').html(alias + minutes + "m " + seconds + "s ");
          }

          // If the count down is finished, write some text
          if (distance < 0) {
            clearInterval(x);
            document.getElementById("demo").innerHTML = "EXPIRED";
          }
        }, 1000);
      });
    },
    ezplus: function ezplus() {
      $(document).ready(function () {
        if (typeof FB != 'undefined') {
          FB.XFBML.parse();
        }
      });
      $("#main_image").ezPlus({
        zoomType: 'lens',
        lensSize: 500,
        scrollZoom: true
      });
    },
    increaseView: function increaseView(id, url) {
      if (typeof id == "undefined") {
        var id = $('#product-detail-id').text();
      }
      if (typeof url == "undefined") {
        var url = window.location.href;
      }
      if (typeof id != "undefined" && id != '') {
        $.get('/product/view/' + id + '?link=' + url, function (res) {});
      }
    },
    getSreenHeight: function getSreenHeight() {
      var viewport_height = window.innerHeight - 60;
      $(".pr_detail").css("height", viewport_height);
    },
    validateForm: function validateForm() {
      if ($("#user-profile").length > 0) {
        var submitButton = $('#profile-submit');
        $('#profile-submit').click(function () {
          $('#user-profile').submit();
        });
        $("#user-profile").validate({
          rules: {
            name: {
              required: true,
              minlength: 2
            }
          },
          messages: {
            name: {
              required: "Bạn phải nhập họ tên",
              minlength: "Bạn phải nhập ít nhất 2 ký tự"
            }
          },
          clearForm: true,
          submitHandler: function submitHandler(form) {
            submitButton.addClass('progress-bar-strip');
            var data = new FormData(form);
            jQuery.ajax({
              processData: false,
              // tell jQuery not to process the data
              contentType: false,
              // tell jQuery not to set contentType
              type: $(form).attr('method'),
              url: $(form).attr('action'),
              data: data,
              success: function success(res) {
                res = JSON.parse(res);
                if (res.success) {
                  submitButton.removeClass('progress-bar-strip');
                  if (typeof res.message != "undefined") var message = res.message;
                  if (res.edit) {
                    $.growl.notice({
                      message: typeof res.message != "undefined" ? res.message : "Sửa bản ghi thành công"
                    });
                    location.href = "/admin/listing/" + MODEL;
                  } else {
                    $.growl.notice({
                      message: typeof res.message != "undefined" ? res.message : "Thêm bản ghi thành công"
                    });
                    location.reload();
                  }
                }
              },
              error: function error(res) {
                submitButton.removeClass('progress-bar-strip');
                console.log('An error occurred.');
                console.log(res);
              }
            });
          },
          error: function error(_respon) {
            console.log(_respon);
          }
        });
      }
    },
    clickEditProfile: function clickEditProfile() {
      if ($('#user-profile').length > 0) {
        $('.edit-field').click(function () {
          var $parent = $(this).parent();
          $parent.removeClass('disabled');
          $parent.find('input[type="text"]').removeAttr('disabled');
          $('#wrapper-submit').show();
        });
      }
    },
    chooseCollectionSearch: function chooseCollectionSearch() {
      var searchForm = $('#search-form');
      $('.fa.fa-search').click(function () {
        searchForm.submit();
      });
      $('#search-form .dropdown-item').click(function (e) {
        $(searchForm).find('button').text($(this).text());
        searchForm.attr('action', $(this).attr('data-href'));
      });
      searchForm.submit(function (event) {
        event.preventDefault();
        var _text = searchForm.find('input[name="search_text"]').val();
        location.href = searchForm.attr("action") + "/" + _text;
      });
    },
    clickTurnOnModal: function clickTurnOnModal() {
      var that = this;
      var currentURL = location.href;
      $('.view-modal').off('click').on('click', function () {
        var URL = $(this).attr('data-url');
        if ($('#product-detail-page').length > 0) {
          location.href = URL;
        } else {
          $('.loadingio-spinner-spinner-ry4x170fish').show();
          var ID = $(this).attr('data-id');
          var CODE = $(this).attr('data-code');
          history.pushState('data', '', URL);
          that.increaseView(ID, URL);
          $.get('/product/modal/' + CODE + '/' + ID + '?productUrl=' + URL, function (_html) {
            $('#product-detail-modal .modal-body').html(_html);
            $('#product-detail-modal').modal('show');
          });
        }
      });
      $('#product-detail-modal').on('show.bs.modal', function () {
        that.swiperSlider();
        that.clickBuyProduct();
        that.clickAddProduct();
        that.clickBtn_loading();
        that.clickCopyClipboard();
        $('.loadingio-spinner-spinner-ry4x170fish').hide();
      });
      $('#product-detail-modal').on('shown.bs.modal', function () {
        document.getElementsByClassName('modal-content')[0].scrollTo({
          top: 0,
          behavior: 'smooth'
        });
        that.clickRelationProduct();
        that.ezplus();
        FB.XFBML.parse();
        window.parsePinBtns();
        $('.share-product').off('click').on('click', function () {
          $('.social-media-share').toggle(500);
        });
        setTimeout(function () {
          $('.share-product').trigger('click');
        }, 1500);
        that.createCountDown();
        that.checkFreeDownload();
      });
      $('#product-detail-modal').on('hide.bs.modal', function () {
        history.pushState('data', '', currentURL);
      });
    },
    clickRelationProduct: function clickRelationProduct() {
      var that = this;
      $('.view-relation-product').off('click').on('click', function () {
        var URL = $(this).attr('data-url');
        if ($('#product-detail-page').length > 0) {
          location.href = URL;
        } else {
          $('.loadingio-spinner-spinner-ry4x170fish').show();
          var ID = $(this).attr('data-id');
          var CODE = $(this).attr('data-code');
          history.pushState('data', '', URL);
          that.increaseView(ID, URL);
          $.get('/product/modal/' + CODE + '/' + ID, function (_html) {
            $('#product-detail-modal .modal-body').html(_html);
            that.swiperSlider();
            $('.loadingio-spinner-spinner-ry4x170fish').hide();
            that.clickRelationProduct();
            that.ezplus();
            FB.XFBML.parse();
            window.parsePinBtns();
            that.clickFavourite();
            that.createCountDown();
            that.checkFreeDownload();
          });
        }
      });
    },
    clickBuyProduct: function clickBuyProduct() {
      $('#btn-buy').click(function () {
        var affiliateAttr = $(this).attr('affiliate');
        var userAffiliateId = null;
        if (typeof affiliateAttr != 'undefined') {
          userAffiliateId = parseInt(affiliateAttr);
        }
        $.ajax({
          url: $(this).attr('data-url'),
          type: "post",
          data: {
            _token: $("input[name='_token']").val(),
            product_id: $(this).attr('data-id'),
            userAffiliateId: userAffiliateId
          },
          success: function success(result) {
            window.location.href = "/cart";
          }
        });
      });
    },
    clickAddProduct: function clickAddProduct() {
      $('.btn-buy-added').off('click').on('click', function (e) {
        var button = $(e.currentTarget);
        var userAffiliateId = null;
        $('.loadingio-spinner-spinner-ry4x170fish').show();
        $(".fa-shopping-cart").addClass("shopping-cart-css");
        $(".total_item").addClass("total_item_shake");
        button.addClass("progress-bar-striped");
        $.ajax({
          url: $(this).attr('data-url'),
          type: "post",
          data: {
            _token: $("input[name='_token']").val(),
            product_id: $(this).attr('data-id'),
            product_code: $(this).attr('data-code')
          },
          success: function success(result) {
            result = JSON.parse(result);
            button.removeClass("progress-bar-striped");
            if (result.success == true) {
              $('.loadingio-spinner-spinner-ry4x170fish').hide();
              //                            setTimeout(() => {
              //                                $('#product-detail-modal').modal('hide')
              //                            }, "500")
              jQuery(".total_item a").html(result.total);
              // console.log(result.total)
              $.growl.notice({
                message: "Đã thêm sản phẩm vào giỏ hàng"
              });
              setTimeout(function () {
                $(".fa-shopping-cart").removeClass("shopping-cart-css");
                $(".total_item").removeClass("total_item_shake");
              }, "500");
            } else if (result.success == false) {
              $(".fa-shopping-cart").removeClass("shopping-cart-css");
              $(".total_item").removeClass("total_item_shake");
              $('.loadingio-spinner-spinner-ry4x170fish').hide();
              $.growl.warning({
                message: "Sản phẩm đã tồn tại trong giỏ hàng!"
              });
            }
          }
        });
      });
    },
    clickCheckAll: function clickCheckAll() {
      $("input[data_check='check_all']").click(function () {
        if ($("input[data_check='check_all']:checked").length != 0) {
          $("input[data_check='check_all_child']:checked").click();
          $("input[data_check='check_all_child']").click();
        } else {
          $("input[data_check='check_all_child']:checked").click();
        }
      });
    },
    clickRemoveProduct: function clickRemoveProduct() {
      $('.remove_product_item').click(function () {
        $('.loadingio-spinner-spinner-ry4x170fish').show();
        $.ajax({
          url: $(this).attr('data-url'),
          type: "post",
          data: {
            _token: $("input[name='_token']").val(),
            row_id: $(this).attr('data-rowId')
          },
          success: function success(result) {
            $('.loadingio-spinner-spinner-ry4x170fish').hide();
            location.reload();
          }
        });
      });
    },
    clickClearProduct: function clickClearProduct() {
      $('#clear_product').click(function () {
        if ($("input[name='product_check']:checked").length > 0) {
          $('.loadingio-spinner-spinner-ry4x170fish').show();
          var row_id = [];
          $("input[name='product_check']:checked").each(function (key, dataValue) {
            console.log("dataValue.value", dataValue.value);
            row_id.push(dataValue.value);
          });
          console.log("rowId", row_id);
          $.ajax({
            url: $(this).attr('data-url'),
            type: "post",
            data: {
              _token: $("input[name='_token']").val(),
              row_id: row_id
            },
            success: function success(result) {
              $('.loadingio-spinner-spinner-ry4x170fish').hide();
              location.reload();
            }
          });
        } else {
          $.growl.warning({
            message: "Bạn chưa chọn sản phẩm nào để xóa!"
          });
        }
      });
    },
    clickToButton: function clickToButton() {
      $('body').on('click', '.btn-buy', function () {
        this.addtocart();
      });
      $('body').on('click', '.goToCart', function () {
        this.addtocart();
      });
    },
    colorFaheart: function colorFaheart() {
      if ($("#product-favourite").length > 0) {
        $.ajax({
          url: $('#product-favourite').attr('data_url'),
          type: "post",
          data: {
            _token: $("input[name='_token']").val()
          },
          success: function success(result) {
            result = JSON.parse(result);
            jQuery.each(result.favorite, function (i, val) {
              $("i[data_id= '" + val + "']").css("color", "#ef2180");
            });
          }
        });
      }
    },
    clickFavourite: function clickFavourite() {
      $('.share-product').off('click').on('click', function () {
        $('.social-media-share').toggle(500);
      });
      setTimeout(function () {
        $('.share-product').trigger('click');
      }, 1500);
      $('.fa-heart, .like-product').off('click').on('click', function () {
        $('.loadingio-spinner-spinner-ry4x170fish').show();
        var target = $(this);
        $.ajax({
          url: $(this).attr('data-url'),
          type: "post",
          data: {
            _token: $("input[name='_token']").val(),
            id: $(this).attr('data_id')
          },
          success: function success(result) {
            $('.loadingio-spinner-spinner-ry4x170fish').hide();
            result = JSON.parse(result);
            if (result.is_favourite) {
              target.css("color", "rgb(239, 33, 128)");
            } else {
              target.css("color", "#8d8989");
              if ($(".info_product_favorite").length > 0) {
                target.closest(".info_product_favorite").remove();
              }
            }
            if (result.success == false) {
              $('.fa-heart').css("color", "#8d8989");
              $.growl.warning({
                message: "Bạn chưa đăng nhập!"
              });
              $('#exampleModal23').modal('show');
            }
          }
        });
      });
    },
    clickBtn_loading: function clickBtn_loading() {
      // $(document).keydown(function (e) {
      //     if (e.which === 123) {
      //         return false;
      //     }
      // });
      // document.addEventListener('contextmenu', event => event.preventDefault());
      $('#fb_load').click(function () {
        $(this).addClass("progress-bar-striped");
      });
      $('#gg_load').click(function () {
        $(this).addClass("progress-bar-striped");
      });
      $('#btn-buy').click(function () {
        $(this).addClass("progress-bar-striped");
      });
      //            $('#btn-add').click(function () {
      //                $(this).addClass("progress-bar-striped");
      //            })
      //            $('#btn_checkout').click(function () {
      //                $(this).addClass("progress-bar-striped");
      //            })
    },
    swiperSlider: function swiperSlider() {
      var swiper = new Swiper(".mySwiper", {
        // slidesPerView: 5,
        spaceBetween: 30,
        slidesPerGroup: 1,
        loop: true,
        loopFillGroupWithBlank: true,
        navigation: {
          nextEl: ".swiper-button-next",
          prevEl: ".swiper-button-prev"
        },
        breakpoints: {
          499: {
            slidesPerView: 1,
            spaceBetweenSlides: 50
          },
          768: {
            slidesPerView: 3,
            spaceBetweenSlides: 50
          },
          999: {
            slidesPerView: 5,
            spaceBetweenSlides: 50
          }
        }
      });
    },
    addToCart: function addToCart() {
      alert('add to cart');
      //            window.location.href = "{{url('/cart')}}"
    }
  };
  var banks = {
    "banksnapas": [{
      "en_name": "An Binh Commercial Joint stock  Bank",
      "vn_name": "Ngân hàng An Bình",
      "bankId": "970425",
      "atmBin": "970425",
      "cardLength": 16,
      "shortName": "ABBank",
      "bankCode": "323",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Asia Commercial Bank",
      "vn_name": "Ngân hàng Á Châu",
      "bankId": "970416",
      "atmBin": "970416",
      "cardLength": 0,
      "shortName": "ACB",
      "bankCode": "307",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Vienam Bank for Agriculture and Rural Development",
      "vn_name": "Ngân hàng NN & PTNT VN",
      "bankId": "970405",
      "atmBin": "970499",
      "cardLength": 16,
      "shortName": "Agribank, VBARD",
      "bankCode": "204",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "ANZ Bank",
      "vn_name": "Ngân hàng ANZ Việt Nam",
      "cardLength": 0,
      "shortName": "ANZ",
      "bankCode": "602",
      "napasSupported": false
    }, {
      "en_name": "BANGKOK  BANK",
      "vn_name": "BANGKOK  BANK",
      "cardLength": 0,
      "shortName": "BANGKOK  BANK",
      "bankCode": "612",
      "napasSupported": false
    }, {
      "en_name": "VietNam national Financial switching Joint Stock Company",
      "vn_name": "Công ty cổ phần chuyển mạch tài chính quốc gia Việt Nam",
      "cardLength": 0,
      "shortName": "Banknetvn",
      "bankCode": "401",
      "napasSupported": false
    }, {
      "en_name": "Baoviet Joint Stock Commercial Bank",
      "vn_name": "Ngân hàng TMCP Bảo Việt",
      "bankId": "970438",
      "atmBin": "970438",
      "cardLength": 20,
      "shortName": "Baoviet Bank",
      "bankCode": "359",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "BANK OF CHINA",
      "vn_name": "BANK OF CHINA",
      "cardLength": 0,
      "shortName": "BC",
      "bankCode": "620",
      "napasSupported": false
    }, {
      "en_name": "Bank for investment and development of Cambodia HCMC",
      "vn_name": "NH ĐT&PT Campuchia CN HCM",
      "cardLength": 0,
      "shortName": "BIDC HCM",
      "bankCode": "648",
      "napasSupported": false
    }, {
      "en_name": "Bank for investment and development of Cambodia HN",
      "vn_name": "NH ĐT&PT Campuchia CN Hà Nội",
      "cardLength": 0,
      "shortName": "BIDC HN",
      "bankCode": "638",
      "napasSupported": false
    }, {
      "en_name": "Bank for Investment and Development of Vietnam",
      "vn_name": "Ngân hàng Đầu tư và Phát triển Việt Nam",
      "bankId": "970418",
      "atmBin": "970418",
      "cardLength": 16,
      "shortName": "BIDV",
      "bankCode": "202",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Bank of Paris and the Netherlands HCMC",
      "vn_name": "BNP Paribas Bank HCM",
      "cardLength": 0,
      "shortName": "BNP Paribas HCM",
      "bankCode": "614",
      "napasSupported": false
    }, {
      "en_name": "BNP Paribas Ha Noi",
      "vn_name": "Ngan hang BNP Paribas CN Ha Noi",
      "cardLength": 0,
      "shortName": "BNP Paribas HN",
      "bankCode": "657",
      "napasSupported": false
    }, {
      "en_name": "Bank of Communications",
      "vn_name": "Bank of Communications",
      "cardLength": 0,
      "shortName": "BOC",
      "bankCode": "615",
      "napasSupported": false
    }, {
      "en_name": "NH BPCEIOM HCMC",
      "vn_name": "Ngân hàng BPCEIOM CN  TP Hồ Chí Minh",
      "cardLength": 0,
      "shortName": "BPCEICOM",
      "bankCode": "601",
      "napasSupported": false
    }, {
      "en_name": "BANK OF TOKYO - MITSUBISHI UFJ - TP HCM",
      "vn_name": "BANK OF TOKYO - MITSUBISHI UFJ - TP HCM",
      "cardLength": 0,
      "shortName": "BTMU HCM",
      "bankCode": "622",
      "napasSupported": false
    }, {
      "en_name": "BANK OF TOKYO - MITSUBISHI UFJ - HN",
      "vn_name": "BANK OF TOKYO - MITSUBISHI UFJ - HN",
      "cardLength": 0,
      "shortName": "BTMU HN",
      "bankCode": "653",
      "napasSupported": false
    }, {
      "en_name": "Credit Agricole Corporate and Investment Bank",
      "vn_name": "Credit Agricole Corporate and Investment Bank",
      "cardLength": 0,
      "shortName": "CACIB",
      "bankCode": "621",
      "napasSupported": false
    }, {
      "en_name": "Commonwealth Bank of Australia",
      "vn_name": "Commonwealth Bank of Australia",
      "cardLength": 0,
      "shortName": "CBA",
      "bankCode": "643",
      "napasSupported": false
    }, {
      "en_name": "China Construction Bank Corporation",
      "vn_name": "China Construction Bank Corporation",
      "cardLength": 0,
      "shortName": "CCBC",
      "bankCode": "611",
      "napasSupported": false
    }, {
      "en_name": "The Chase Manhattan Bank",
      "vn_name": "The Chase Manhattan Bank",
      "cardLength": 0,
      "shortName": "CHASE",
      "bankCode": "627",
      "napasSupported": false
    }, {
      "en_name": "CIMB Bank Vietnam Limited",
      "vn_name": "Ngân hàng TNHH MTV CIMB Việt Nam",
      "bankId": "422589",
      "atmBin": "422589",
      "cardLength": 0,
      "shortName": "CIMB",
      "bankCode": "661",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "CitiBank HCM",
      "vn_name": "Citi Bank TP HCM",
      "cardLength": 0,
      "shortName": "CitibankHCM",
      "bankCode": "654",
      "napasSupported": false
    }, {
      "en_name": "Citibank Ha Noi",
      "vn_name": "Citi Bank Ha Noi",
      "cardLength": 0,
      "shortName": "CitibankHN",
      "bankCode": "605",
      "napasSupported": false
    }, {
      "en_name": "Co-Operation Bank of Viet Nam",
      "vn_name": "Ngân hàng Hợp tác Việt Nam",
      "cardLength": 0,
      "shortName": "COOPBANK",
      "bankCode": "901",
      "napasSupported": false
    }, {
      "en_name": "The ChinaTrust Commercial Bank HCMC",
      "vn_name": "Ngân hàng CTBC CN TP Hồ Chí Minh",
      "cardLength": 0,
      "shortName": "CTBC",
      "bankCode": "629",
      "napasSupported": false
    }, {
      "en_name": "Cathay United Bank",
      "vn_name": "Ngân hàng Cathay",
      "cardLength": 0,
      "shortName": "CTU",
      "bankCode": "634",
      "napasSupported": false
    }, {
      "en_name": "DEUTSCHE BANK",
      "vn_name": "DEUTSCHE BANK",
      "cardLength": 0,
      "shortName": "DB",
      "bankCode": "619",
      "napasSupported": false
    }, {
      "en_name": "DBS Bank Ltd",
      "vn_name": "DBS Bank Ltd",
      "cardLength": 0,
      "shortName": "DBS",
      "bankCode": "650",
      "napasSupported": false
    }, {
      "en_name": "Dong A Commercial Joint stock Bank",
      "vn_name": "Ngân hàng Đông Á",
      "bankId": "970406",
      "atmBin": "970406",
      "cardLength": 16,
      "shortName": "Dong A Bank, DAB",
      "bankCode": "304",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Vietnam Export Import Commercial Joint Stock Bank",
      "vn_name": "Ngân hàng Xuất nhập khẩu Việt Nam",
      "bankId": "970431",
      "atmBin": "970431",
      "cardLength": 16,
      "shortName": "Eximbank, EIB",
      "bankCode": "305",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "First Commercial Bank",
      "vn_name": "First Commercial Bank",
      "cardLength": 0,
      "shortName": "FCNB",
      "bankCode": "630",
      "napasSupported": false
    }, {
      "en_name": "First Commercial Bank Ha Noi",
      "vn_name": "First Commercial Bank Ha Noi",
      "cardLength": 0,
      "shortName": "FCNB HN",
      "bankCode": "608",
      "napasSupported": false
    }, {
      "en_name": "Global Petro Commercial Joint Stock Bank",
      "vn_name": "Ngân hàng Dầu khí Toàn cầu",
      "bankId": "970408",
      "atmBin": "970408",
      "cardLength": 20,
      "shortName": "GP Bank",
      "bankCode": "320",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Housing Development Bank",
      "vn_name": "Ngân hàng Phát triển TP HCM",
      "bankId": "970437",
      "atmBin": "970437",
      "cardLength": 20,
      "shortName": "HDBank",
      "bankCode": "321",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Hong Leong Bank Viet Nam",
      "vn_name": "Ngân hàng Hong Leong Viet Nam",
      "bankId": "970442",
      "atmBin": "970442",
      "cardLength": 20,
      "shortName": "HLO",
      "bankCode": "603",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Hua Nan Commercial Bank",
      "vn_name": "Hua Nan Commercial Bank",
      "cardLength": 0,
      "shortName": "HNCB",
      "bankCode": "640",
      "napasSupported": false
    }, {
      "en_name": "The HongKong and Shanghai Banking Corporation",
      "vn_name": "NH TNHH Một Thành Viên HSBC Việt Nam",
      "cardLength": 0,
      "shortName": "HSBC",
      "bankCode": "617",
      "napasSupported": false
    }, {
      "en_name": "NH The Hongkong and Shanghai",
      "vn_name": "Ngân hàng The Hongkong và Thượng Hải",
      "cardLength": 0,
      "shortName": "HSBC HN",
      "bankCode": "645",
      "napasSupported": false
    }, {
      "en_name": "Industrial Bank of Korea",
      "vn_name": "Industrial Bank of Korea",
      "cardLength": 0,
      "shortName": "IBK",
      "bankCode": "641",
      "napasSupported": false
    }, {
      "en_name": "ICB of China CN Ha Noi",
      "vn_name": "ICB of China CN Ha Noi",
      "cardLength": 0,
      "shortName": "ICB",
      "bankCode": "649",
      "napasSupported": false
    }, {
      "en_name": "Indovina Bank",
      "vn_name": "Indovina Bank",
      "bankId": "970434",
      "atmBin": "888999",
      "cardLength": 0,
      "shortName": "IVB",
      "bankCode": "502",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Kho Bac Nha Nuoc",
      "vn_name": "Kho Bạc Nhà Nước",
      "cardLength": 0,
      "shortName": "KBNN",
      "bankCode": "701",
      "napasSupported": false
    }, {
      "en_name": "Korea Exchange Bank",
      "vn_name": "Korea Exchange Bank",
      "cardLength": 0,
      "shortName": "KEB",
      "bankCode": "626",
      "napasSupported": false
    }, {
      "en_name": "Kien Long Commercial Joint Stock Bank",
      "vn_name": "Ngân hàng Kiên Long",
      "bankId": "970452",
      "atmBin": "970452",
      "cardLength": 16,
      "shortName": "Kienlongbank",
      "bankCode": "353",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Kookmin Bank",
      "vn_name": "Ngân hàng Kookmin",
      "cardLength": 0,
      "shortName": "KMB",
      "bankCode": "631",
      "napasSupported": false
    }, {
      "en_name": "Lien Viet Post Bank",
      "vn_name": "Ngan hàng TMCP Bưu điện Liên Việt",
      "bankId": "970449",
      "atmBin": "970449",
      "cardLength": 0,
      "shortName": "Lienvietbank,  LPB",
      "bankCode": "357",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Maritime Bank",
      "vn_name": "Ngân hàng Hàng Hải Việt Nam",
      "bankId": "970426",
      "atmBin": "970426",
      "cardLength": 16,
      "shortName": "Maritime Bank, MSB",
      "bankCode": "302",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Maybank",
      "vn_name": "Malayan Banking Berhad",
      "cardLength": 0,
      "shortName": "Maybank",
      "bankCode": "609",
      "napasSupported": false
    }, {
      "en_name": "Military Commercial Joint stock Bank",
      "vn_name": "Ngân hàng Quân Đội",
      "bankId": "970422",
      "atmBin": "970422",
      "cardLength": 16,
      "shortName": "MB",
      "bankCode": "311",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Malayan Banking Berhad",
      "vn_name": "Malayan Banking Berhad",
      "cardLength": 0,
      "shortName": "MBB",
      "bankCode": "635",
      "napasSupported": false
    }, {
      "en_name": "Mizuho Corporate Bank - TP HCM",
      "vn_name": "Mizuho Corporate Bank - TP HCM",
      "cardLength": 0,
      "shortName": "MCB_HCM",
      "bankCode": "639",
      "napasSupported": false
    }, {
      "en_name": "Mega ICBC Bank",
      "vn_name": "Mega ICBC Bank",
      "cardLength": 0,
      "shortName": "MICB",
      "bankCode": "623",
      "napasSupported": false
    }, {
      "en_name": "Mizuho Bank",
      "vn_name": "Mizuho Corporate Bank",
      "cardLength": 0,
      "shortName": "Mizuho Bank",
      "bankCode": "613",
      "napasSupported": false
    }, {
      "en_name": "Nam A Commercial Joint stock Bank",
      "vn_name": "Ngân hàng Nam Á",
      "bankId": "970428",
      "atmBin": "970428",
      "cardLength": 0,
      "shortName": "Nam A Bank, NAB",
      "bankCode": "306",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "North Asia Commercial Joint Stock Bank",
      "vn_name": "Ngân hàng Bắc Á",
      "bankId": "970409",
      "atmBin": "970409",
      "cardLength": 0,
      "shortName": "NASBank, NASB",
      "bankCode": "313",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "National Citizen Bank",
      "vn_name": "Ngân hàng Quoc Dan",
      "bankId": "970419",
      "atmBin": "970419",
      "cardLength": 16,
      "shortName": "NCB",
      "bankCode": "352",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Oversea - Chinese Banking Corporation",
      "vn_name": "Oversea - Chinese Bank",
      "cardLength": 0,
      "shortName": "OCBC",
      "bankCode": "625",
      "napasSupported": false
    }, {
      "en_name": "Ocean Bank",
      "vn_name": "Ngân hàng Đại Dương",
      "bankId": "970414",
      "atmBin": "970414",
      "cardLength": 20,
      "shortName": "Ocean Bank",
      "bankCode": "319",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Orient Commercial Joint Stock Bank",
      "vn_name": "Ngân hàng Phương Đông",
      "bankId": "970448",
      "atmBin": "970448",
      "cardLength": 16,
      "shortName": "Oricombank, OCB, PhuongDong Bank",
      "bankCode": "333",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Petrolimex group commercial Joint stock Bank",
      "vn_name": "Ngân hàng Xăng dầu Petrolimex",
      "bankId": "970430",
      "atmBin": "970430",
      "cardLength": 16,
      "shortName": "PG Bank",
      "bankCode": "341",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "PVcombank",
      "vn_name": "NH TMCP Đại Chúng Viet Nam",
      "bankId": "970412",
      "atmBin": "970412",
      "cardLength": 16,
      "shortName": "PVcombank",
      "bankCode": "360",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Quy tin dung co so",
      "vn_name": "Quỹ tín dụng cơ sở",
      "cardLength": 0,
      "shortName": "QTDCS",
      "bankCode": "902",
      "napasSupported": false
    }, {
      "en_name": "Saigon Thuong Tin Commercial Joint Stock Bank",
      "vn_name": "Ngân hàng Sài Gòn Thương Tín",
      "bankId": "970403",
      "atmBin": "970403",
      "cardLength": 16,
      "shortName": "Sacombank",
      "bankCode": "303",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Saigon Bank for Industry and Trade",
      "vn_name": "Ngân hàng Sài Gòn Công Thương",
      "bankId": "970400",
      "atmBin": "161087",
      "cardLength": 16,
      "shortName": "Saigonbank",
      "bankCode": "308",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "State Bank of Vietnam",
      "vn_name": "Ngân Hàng Nhà Nước",
      "cardLength": 0,
      "shortName": "SBV",
      "bankCode": "101",
      "napasSupported": false
    }, {
      "en_name": "Saigon Commercial Joint Stock Bank",
      "vn_name": "Ngân hàng TMCP Sài Gòn",
      "bankId": "970429",
      "atmBin": "970429",
      "cardLength": 16,
      "shortName": "SCB",
      "bankCode": "334",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Standard Chartered Bank",
      "vn_name": "Ngân hàng Standard Chartered Bank Việt Nam",
      "cardLength": 0,
      "shortName": "SCBank",
      "bankCode": "604",
      "napasSupported": false
    }, {
      "en_name": "Standard Chartered Bank HN",
      "vn_name": "Ngân hàng Standard Chartered Bank HN",
      "cardLength": 0,
      "shortName": "SCBank HN",
      "bankCode": "646",
      "napasSupported": false
    }, {
      "en_name": "The Shanghai Commercial & Savings Bank CN Dong Nai",
      "vn_name": "The Shanghai Commercial & Savings Bank CN Đồng Nai",
      "cardLength": 0,
      "shortName": "SCSB",
      "bankCode": "606",
      "napasSupported": false
    }, {
      "en_name": "South East Asia Commercial Joint stock  Bank",
      "vn_name": "Ngân hàng TMCP Đông Nam Á",
      "bankId": "970440",
      "atmBin": "970468",
      "cardLength": 16,
      "shortName": "SeABank",
      "bankCode": "317",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Saigon - Hanoi Commercial Joint Stock Bank",
      "vn_name": "Ngân hàng Sài Gòn - Hà Nội",
      "bankId": "970443",
      "atmBin": "970443",
      "cardLength": 16,
      "shortName": "SHB",
      "bankCode": "348",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Shinhan Bank",
      "vn_name": "Ngân hàng TNHH MTV Shinhan Việt Nam",
      "bankId": "970424",
      "atmBin": "970424",
      "cardLength": 0,
      "shortName": "Shinhan Bank",
      "bankCode": "616",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "The Siam Commercial Public Bank",
      "vn_name": "Ngân hàng The Siam Commercial Public",
      "cardLength": 0,
      "shortName": "SIAM",
      "bankCode": "600",
      "napasSupported": false
    }, {
      "en_name": "Sumitomo Mitsui Banking Corporation HCMC",
      "vn_name": "Sumitomo Mitsui Banking Corporation HCM",
      "cardLength": 0,
      "shortName": "SMBC",
      "bankCode": "636",
      "napasSupported": false
    }, {
      "en_name": "Sumitomo Mitsui Banking Corporation HN",
      "vn_name": "Sumitomo Mitsui Banking Corporation HN",
      "cardLength": 0,
      "shortName": "SMBC HN",
      "bankCode": "936",
      "napasSupported": false
    }, {
      "en_name": "SinoPac Bank",
      "vn_name": "Ngân hàng SinoPac",
      "cardLength": 0,
      "shortName": "SPB",
      "bankCode": "632",
      "napasSupported": false
    }, {
      "en_name": "Vietnam Technological and Commercial Joint stock Bank",
      "vn_name": "Ngân hàng Kỹ thương Việt Nam",
      "bankId": "970407",
      "atmBin": "970407",
      "cardLength": 16,
      "shortName": "Techcombank, TCB",
      "bankCode": "310",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Taipei Fubon Commercial Bank Ha Noi",
      "vn_name": "Taipei Fubon Commercial Bank Ha Noi",
      "cardLength": 0,
      "shortName": "TFCBHN",
      "bankCode": "642",
      "napasSupported": false
    }, {
      "en_name": "Taipei Fubon Commercial Bank TP Ho Chi Minh",
      "vn_name": "Taipei Fubon Commercial Bank TP Ho Chi Minh",
      "cardLength": 0,
      "shortName": "TFCBTPHCM",
      "bankCode": "651",
      "napasSupported": false
    }, {
      "en_name": "United Oversea Bank",
      "vn_name": "United Oversea Bank",
      "bankId": "970458",
      "atmBin": "970458",
      "cardLength": 0,
      "shortName": "UOB",
      "bankCode": "618",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Vietnam Bank for Social Policies",
      "vn_name": "Ngân hàng Chính sách xã hội Việt Nam",
      "cardLength": 0,
      "shortName": "VBSP",
      "bankCode": "207",
      "napasSupported": false
    }, {
      "en_name": "Vietnam Development Bank",
      "vn_name": "Ngân hàng Phát triển Việt Nam",
      "cardLength": 0,
      "shortName": "VDB",
      "bankCode": "208",
      "napasSupported": false
    }, {
      "en_name": "Vietnam International Commercial Joint Stock Bank",
      "vn_name": "Ngân hàng Quốc tế",
      "bankId": "970441",
      "atmBin": "970441",
      "cardLength": 0,
      "shortName": "VIBank, VIB",
      "bankCode": "314",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "VID public",
      "vn_name": "Ngân hàng VID Public",
      "bankId": "970439",
      "atmBin": "970439",
      "cardLength": 16,
      "shortName": "VID public",
      "bankCode": "501",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Ngan hang Viet Hoa",
      "vn_name": "Ngân hàng Việt Hoa",
      "cardLength": 0,
      "shortName": "Viet Hoa Bank",
      "bankCode": "324",
      "napasSupported": false
    }, {
      "en_name": "Viet A Commercial Joint Stock Bank",
      "vn_name": "Ngân hàng Việt Á",
      "bankId": "970427",
      "atmBin": "970427",
      "cardLength": 0,
      "shortName": "VietA Bank, VAB",
      "bankCode": "355",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Vietnam Thương tin Commercial Joint Stock Bank",
      "vn_name": "Ngân hàng Việt Nam Thương Tín",
      "bankId": "970433",
      "atmBin": "970433",
      "cardLength": 16,
      "shortName": "Vietbank",
      "bankCode": "356",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "BanViet Commercial Jont stock Bank",
      "vn_name": "NHTMCP Bản Việt",
      "bankId": "970454",
      "atmBin": "970454",
      "cardLength": 16,
      "shortName": "VietCapital Bank",
      "bankCode": "327",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Joint Stock Commercial Bank for Foreign Trade of Vietnam",
      "vn_name": "Ngân hàng TMCP Ngoại Thương",
      "bankId": "970436",
      "atmBin": "970436",
      "cardLength": 0,
      "shortName": "Vietcombank, VCB",
      "bankCode": "203",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Vietnam Joint Stock Commercial Bank for Industry and Trade",
      "vn_name": "Ngân hàng công thương Việt Nam",
      "bankId": "970415",
      "atmBin": "970415",
      "cardLength": 16,
      "shortName": "Vietinbank",
      "bankCode": "201",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Vietnam Construction Bank",
      "vn_name": "NH TMCP Xây dựng Việt Nam",
      "cardLength": 0,
      "shortName": "VNCB",
      "bankCode": "339",
      "napasSupported": false
    }, {
      "en_name": "Vietnam prosperity Joint stock commercial Bank",
      "vn_name": "Ngân hàng Thương mại cổ phần Việt Nam Thịnh Vượng",
      "bankId": "970432",
      "atmBin": "970432",
      "cardLength": 16,
      "shortName": "VPBank",
      "bankCode": "309",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Vietnam - Russia Bank",
      "vn_name": "Ngân hàng Liên doanh Việt Nga",
      "bankId": "970421",
      "atmBin": "970421",
      "cardLength": 16,
      "shortName": "VRB",
      "bankCode": "505",
      "type": "ACC",
      "napasSupported": true
    }, {
      "en_name": "Ngan hang Vung Tau",
      "vn_name": "Ngân hàng Vũng Tàu",
      "cardLength": 0,
      "shortName": "Vung Tau",
      "bankCode": "315",
      "napasSupported": false
    }, {
      "en_name": "Woori BANK HCMC",
      "vn_name": "NH Woori HCM",
      "cardLength": 0,
      "shortName": "WHHCM",
      "bankCode": "637",
      "napasSupported": false
    }, {
      "en_name": "WOORI BANK Hanoi",
      "vn_name": "WOORI BANK Hà Nội",
      "bankId": "970457",
      "atmBin": "970457",
      "cardLength": 0,
      "shortName": "WHHN",
      "bankCode": "624",
      "type": "ACC",
      "napasSupported": true
    }]
  };
  global.Frontend = new Frontend();
})(this);

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module is referenced by other modules so it can't be inlined
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./resources/js/frontend.js"]();
/******/ 	
/******/ })()
;