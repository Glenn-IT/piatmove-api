<Global.Microsoft.VisualBasic.CompilerServices.DesignerGenerated()>
Partial Class UcAddRecord
    Inherits System.Windows.Forms.UserControl

    <System.Diagnostics.DebuggerNonUserCode()>
    Protected Overrides Sub Dispose(disposing As Boolean)
        Try
            If disposing AndAlso components IsNot Nothing Then
                components.Dispose()
            End If
        Finally
            MyBase.Dispose(disposing)
        End Try
    End Sub

    Private components As System.ComponentModel.IContainer

    <System.Diagnostics.DebuggerStepThrough()>
    Private Sub InitializeComponent()
        pnlFormCard = New Panel()
        lblPageTitle = New Label()
        lblPageSub = New Label()
        pnlDivider = New Panel()
        lblIncidentNo = New Label()
        txtIncidentNo = New TextBox()
        lblIncidentType = New Label()
        cboIncidentType = New ComboBox()
        lblIncidentDateTime = New Label()
        dtpIncidentDateTime = New DateTimePicker()
        lblAlarmLevel = New Label()
        cboAlarmLevel = New ComboBox()
        lblResponseTime = New Label()
        dtpResponseTime = New DateTimePicker()
        lblStatus = New Label()
        cboStatus = New ComboBox()
        lblInvolvedProperty = New Label()
        txtInvolvedProperty = New TextBox()
        lblAddress = New Label()
        txtAddress = New TextBox()
        lblOwnerOccupant = New Label()
        txtOwnerOccupant = New TextBox()
        lblCallerInformation = New Label()
        txtCallerInformation = New TextBox()
        lblCasualties = New Label()
        txtCasualties = New TextBox()
        lblDamageEstimate = New Label()
        txtDamageEstimate = New TextBox()
        lblCauseOfFire = New Label()
        txtCauseOfFire = New TextBox()
        lblRemarks = New Label()
        txtRemarks = New TextBox()
        lblDocument = New Label()
        txtDocumentName = New TextBox()
        btnBrowseDocument = New Button()
        btnSave = New Button()
        btnClear = New Button()
        pnlFormCard.SuspendLayout()
        SuspendLayout()
        ' 
        ' pnlFormCard
        ' 
        pnlFormCard.Anchor = AnchorStyles.Top Or AnchorStyles.Left Or AnchorStyles.Right
        pnlFormCard.BackColor = Color.White
        pnlFormCard.Controls.Add(lblPageTitle)
        pnlFormCard.Controls.Add(lblPageSub)
        pnlFormCard.Controls.Add(pnlDivider)
        pnlFormCard.Controls.Add(lblIncidentNo)
        pnlFormCard.Controls.Add(txtIncidentNo)
        pnlFormCard.Controls.Add(lblIncidentType)
        pnlFormCard.Controls.Add(cboIncidentType)
        pnlFormCard.Controls.Add(lblIncidentDateTime)
        pnlFormCard.Controls.Add(dtpIncidentDateTime)
        pnlFormCard.Controls.Add(lblAlarmLevel)
        pnlFormCard.Controls.Add(cboAlarmLevel)
        pnlFormCard.Controls.Add(lblResponseTime)
        pnlFormCard.Controls.Add(dtpResponseTime)
        pnlFormCard.Controls.Add(lblStatus)
        pnlFormCard.Controls.Add(cboStatus)
        pnlFormCard.Controls.Add(lblInvolvedProperty)
        pnlFormCard.Controls.Add(txtInvolvedProperty)
        pnlFormCard.Controls.Add(lblAddress)
        pnlFormCard.Controls.Add(txtAddress)
        pnlFormCard.Controls.Add(lblOwnerOccupant)
        pnlFormCard.Controls.Add(txtOwnerOccupant)
        pnlFormCard.Controls.Add(lblCallerInformation)
        pnlFormCard.Controls.Add(txtCallerInformation)
        pnlFormCard.Controls.Add(lblCasualties)
        pnlFormCard.Controls.Add(txtCasualties)
        pnlFormCard.Controls.Add(lblDamageEstimate)
        pnlFormCard.Controls.Add(txtDamageEstimate)
        pnlFormCard.Controls.Add(lblCauseOfFire)
        pnlFormCard.Controls.Add(txtCauseOfFire)
        pnlFormCard.Controls.Add(lblRemarks)
        pnlFormCard.Controls.Add(txtRemarks)
        pnlFormCard.Controls.Add(lblDocument)
        pnlFormCard.Controls.Add(txtDocumentName)
        pnlFormCard.Controls.Add(btnBrowseDocument)
        pnlFormCard.Controls.Add(btnSave)
        pnlFormCard.Controls.Add(btnClear)
        pnlFormCard.Location = New Point(35, 27)
        pnlFormCard.Margin = New Padding(4)
        pnlFormCard.Name = "pnlFormCard"
        pnlFormCard.Size = New Size(1608, 959)
        pnlFormCard.TabIndex = 0
        ' 
        ' lblPageTitle
        ' 
        lblPageTitle.Font = New Font("Segoe UI", 15F, FontStyle.Bold)
        lblPageTitle.ForeColor = Color.FromArgb(CByte(30), CByte(30), CByte(30))
        lblPageTitle.Location = New Point(35, 27)
        lblPageTitle.Margin = New Padding(4, 0, 4, 0)
        lblPageTitle.Name = "lblPageTitle"
        lblPageTitle.Size = New Size(899, 52)
        lblPageTitle.TabIndex = 0
        lblPageTitle.Text = "Add New Incident Record"
        ' 
        ' lblPageSub
        ' 
        lblPageSub.Font = New Font("Segoe UI", 9F)
        lblPageSub.ForeColor = Color.Gray
        lblPageSub.Location = New Point(35, 77)
        lblPageSub.Margin = New Padding(4, 0, 4, 0)
        lblPageSub.Name = "lblPageSub"
        lblPageSub.Size = New Size(899, 33)
        lblPageSub.TabIndex = 1
        lblPageSub.Text = "Fill in all required fields (*) to add a new record."
        ' 
        ' pnlDivider
        ' 
        pnlDivider.Anchor = AnchorStyles.Top Or AnchorStyles.Left Or AnchorStyles.Right
        pnlDivider.BackColor = Color.FromArgb(CByte(180), CByte(20), CByte(20))
        pnlDivider.Location = New Point(35, 120)
        pnlDivider.Margin = New Padding(4)
        pnlDivider.Name = "pnlDivider"
        pnlDivider.Size = New Size(1536, 4)
        pnlDivider.TabIndex = 2
        ' 
        ' lblIncidentNo
        ' 
        lblIncidentNo.Font = New Font("Segoe UI", 9F, FontStyle.Bold)
        lblIncidentNo.ForeColor = Color.FromArgb(CByte(70), CByte(70), CByte(70))
        lblIncidentNo.Location = New Point(35, 149)
        lblIncidentNo.Margin = New Padding(4, 0, 4, 0)
        lblIncidentNo.Name = "lblIncidentNo"
        lblIncidentNo.Size = New Size(586, 30)
        lblIncidentNo.TabIndex = 3
        lblIncidentNo.Text = "Incident No. *"
        ' 
        ' txtIncidentNo
        ' 
        txtIncidentNo.BackColor = Color.FromArgb(CByte(250), CByte(250), CByte(250))
        txtIncidentNo.BorderStyle = BorderStyle.FixedSingle
        txtIncidentNo.Font = New Font("Segoe UI", 10F)
        txtIncidentNo.Location = New Point(35, 183)
        txtIncidentNo.Margin = New Padding(4)
        txtIncidentNo.Name = "txtIncidentNo"
        txtIncidentNo.PlaceholderText = "e.g. INC-2025-001"
        txtIncidentNo.Size = New Size(583, 34)
        txtIncidentNo.TabIndex = 4
        ' 
        ' lblIncidentType
        ' 
        lblIncidentType.Anchor = AnchorStyles.Top Or AnchorStyles.Right
        lblIncidentType.Font = New Font("Segoe UI", 9F, FontStyle.Bold)
        lblIncidentType.ForeColor = Color.FromArgb(CByte(70), CByte(70), CByte(70))
        lblIncidentType.Location = New Point(951, 149)
        lblIncidentType.Margin = New Padding(4, 0, 4, 0)
        lblIncidentType.Name = "lblIncidentType"
        lblIncidentType.Size = New Size(586, 30)
        lblIncidentType.TabIndex = 5
        lblIncidentType.Text = "Incident Type *"
        ' 
        ' cboIncidentType
        ' 
        cboIncidentType.Anchor = AnchorStyles.Top Or AnchorStyles.Right
        cboIncidentType.BackColor = Color.FromArgb(CByte(250), CByte(250), CByte(250))
        cboIncidentType.DropDownStyle = ComboBoxStyle.DropDownList
        cboIncidentType.FlatStyle = FlatStyle.Flat
        cboIncidentType.Font = New Font("Segoe UI", 10F)
        cboIncidentType.Location = New Point(951, 183)
        cboIncidentType.Margin = New Padding(4)
        cboIncidentType.Name = "cboIncidentType"
        cboIncidentType.Size = New Size(583, 36)
        cboIncidentType.TabIndex = 6
        ' 
        ' lblIncidentDateTime
        ' 
        lblIncidentDateTime.Font = New Font("Segoe UI", 9F, FontStyle.Bold)
        lblIncidentDateTime.ForeColor = Color.FromArgb(CByte(70), CByte(70), CByte(70))
        lblIncidentDateTime.Location = New Point(35, 224)
        lblIncidentDateTime.Margin = New Padding(4, 0, 4, 0)
        lblIncidentDateTime.Name = "lblIncidentDateTime"
        lblIncidentDateTime.Size = New Size(586, 30)
        lblIncidentDateTime.TabIndex = 7
        lblIncidentDateTime.Text = "Incident Date && Time *"
        ' 
        ' dtpIncidentDateTime
        ' 
        dtpIncidentDateTime.CustomFormat = "MM/dd/yyyy HHmm"
        dtpIncidentDateTime.Font = New Font("Segoe UI", 10F)
        dtpIncidentDateTime.Format = DateTimePickerFormat.Custom
        dtpIncidentDateTime.Location = New Point(35, 257)
        dtpIncidentDateTime.Margin = New Padding(4)
        dtpIncidentDateTime.Name = "dtpIncidentDateTime"
        dtpIncidentDateTime.Size = New Size(583, 34)
        dtpIncidentDateTime.TabIndex = 8
        ' 
        ' lblAlarmLevel
        ' 
        lblAlarmLevel.Anchor = AnchorStyles.Top Or AnchorStyles.Right
        lblAlarmLevel.Font = New Font("Segoe UI", 9F, FontStyle.Bold)
        lblAlarmLevel.ForeColor = Color.FromArgb(CByte(70), CByte(70), CByte(70))
        lblAlarmLevel.Location = New Point(951, 226)
        lblAlarmLevel.Margin = New Padding(4, 0, 4, 0)
        lblAlarmLevel.Name = "lblAlarmLevel"
        lblAlarmLevel.Size = New Size(586, 30)
        lblAlarmLevel.TabIndex = 9
        lblAlarmLevel.Text = "Alarm Level *"
        ' 
        ' cboAlarmLevel
        ' 
        cboAlarmLevel.Anchor = AnchorStyles.Top Or AnchorStyles.Right
        cboAlarmLevel.BackColor = Color.FromArgb(CByte(250), CByte(250), CByte(250))
        cboAlarmLevel.DropDownStyle = ComboBoxStyle.DropDownList
        cboAlarmLevel.FlatStyle = FlatStyle.Flat
        cboAlarmLevel.Font = New Font("Segoe UI", 10F)
        cboAlarmLevel.Location = New Point(951, 258)
        cboAlarmLevel.Margin = New Padding(4)
        cboAlarmLevel.Name = "cboAlarmLevel"
        cboAlarmLevel.Size = New Size(583, 36)
        cboAlarmLevel.TabIndex = 10
        ' 
        ' lblResponseTime
        ' 
        lblResponseTime.Font = New Font("Segoe UI", 9F, FontStyle.Bold)
        lblResponseTime.ForeColor = Color.FromArgb(CByte(70), CByte(70), CByte(70))
        lblResponseTime.Location = New Point(35, 298)
        lblResponseTime.Margin = New Padding(4, 0, 4, 0)
        lblResponseTime.Name = "lblResponseTime"
        lblResponseTime.Size = New Size(586, 30)
        lblResponseTime.TabIndex = 11
        lblResponseTime.Text = "Response Time"
        ' 
        ' dtpResponseTime
        ' 
        dtpResponseTime.CustomFormat = "HHmm"
        dtpResponseTime.Font = New Font("Segoe UI", 10F)
        dtpResponseTime.Format = DateTimePickerFormat.Custom
        dtpResponseTime.Location = New Point(35, 330)
        dtpResponseTime.Margin = New Padding(4)
        dtpResponseTime.Name = "dtpResponseTime"
        dtpResponseTime.ShowCheckBox = True
        dtpResponseTime.ShowUpDown = True
        dtpResponseTime.Size = New Size(583, 34)
        dtpResponseTime.TabIndex = 12
        ' 
        ' lblStatus
        ' 
        lblStatus.Anchor = AnchorStyles.Top Or AnchorStyles.Right
        lblStatus.Font = New Font("Segoe UI", 9F, FontStyle.Bold)
        lblStatus.ForeColor = Color.FromArgb(CByte(70), CByte(70), CByte(70))
        lblStatus.Location = New Point(951, 300)
        lblStatus.Margin = New Padding(4, 0, 4, 0)
        lblStatus.Name = "lblStatus"
        lblStatus.Size = New Size(586, 30)
        lblStatus.TabIndex = 13
        lblStatus.Text = "Status *"
        ' 
        ' cboStatus
        ' 
        cboStatus.Anchor = AnchorStyles.Top Or AnchorStyles.Right
        cboStatus.BackColor = Color.FromArgb(CByte(250), CByte(250), CByte(250))
        cboStatus.DropDownStyle = ComboBoxStyle.DropDownList
        cboStatus.FlatStyle = FlatStyle.Flat
        cboStatus.Font = New Font("Segoe UI", 10F)
        cboStatus.Location = New Point(951, 333)
        cboStatus.Margin = New Padding(4)
        cboStatus.Name = "cboStatus"
        cboStatus.Size = New Size(583, 36)
        cboStatus.TabIndex = 14
        ' 
        ' lblInvolvedProperty
        ' 
        lblInvolvedProperty.Font = New Font("Segoe UI", 9F, FontStyle.Bold)
        lblInvolvedProperty.ForeColor = Color.FromArgb(CByte(70), CByte(70), CByte(70))
        lblInvolvedProperty.Location = New Point(35, 384)
        lblInvolvedProperty.Margin = New Padding(4, 0, 4, 0)
        lblInvolvedProperty.Name = "lblInvolvedProperty"
        lblInvolvedProperty.Size = New Size(899, 30)
        lblInvolvedProperty.TabIndex = 15
        lblInvolvedProperty.Text = "Involved Building / Establishment / Vehicle *"
        ' 
        ' txtInvolvedProperty
        ' 
        txtInvolvedProperty.Anchor = AnchorStyles.Top Or AnchorStyles.Left Or AnchorStyles.Right
        txtInvolvedProperty.BackColor = Color.FromArgb(CByte(250), CByte(250), CByte(250))
        txtInvolvedProperty.BorderStyle = BorderStyle.FixedSingle
        txtInvolvedProperty.Font = New Font("Segoe UI", 10F)
        txtInvolvedProperty.Location = New Point(35, 418)
        txtInvolvedProperty.Margin = New Padding(4)
        txtInvolvedProperty.Name = "txtInvolvedProperty"
        txtInvolvedProperty.PlaceholderText = "e.g. Dela Cruz Residence"
        txtInvolvedProperty.Size = New Size(1499, 34)
        txtInvolvedProperty.TabIndex = 16
        ' 
        ' lblAddress
        ' 
        lblAddress.Font = New Font("Segoe UI", 9F, FontStyle.Bold)
        lblAddress.ForeColor = Color.FromArgb(CByte(70), CByte(70), CByte(70))
        lblAddress.Location = New Point(35, 459)
        lblAddress.Margin = New Padding(4, 0, 4, 0)
        lblAddress.Name = "lblAddress"
        lblAddress.Size = New Size(899, 30)
        lblAddress.TabIndex = 17
        lblAddress.Text = "Incident Address / Location *"
        ' 
        ' txtAddress
        ' 
        txtAddress.Anchor = AnchorStyles.Top Or AnchorStyles.Left Or AnchorStyles.Right
        txtAddress.BackColor = Color.FromArgb(CByte(250), CByte(250), CByte(250))
        txtAddress.BorderStyle = BorderStyle.FixedSingle
        txtAddress.Font = New Font("Segoe UI", 10F)
        txtAddress.Location = New Point(35, 492)
        txtAddress.Margin = New Padding(4)
        txtAddress.Name = "txtAddress"
        txtAddress.PlaceholderText = "e.g. Brgy. Centro, Tuao, Cagayan"
        txtAddress.Size = New Size(1499, 34)
        txtAddress.TabIndex = 18
        ' 
        ' lblOwnerOccupant
        ' 
        lblOwnerOccupant.Font = New Font("Segoe UI", 9F, FontStyle.Bold)
        lblOwnerOccupant.ForeColor = Color.FromArgb(CByte(70), CByte(70), CByte(70))
        lblOwnerOccupant.Location = New Point(35, 533)
        lblOwnerOccupant.Margin = New Padding(4, 0, 4, 0)
        lblOwnerOccupant.Name = "lblOwnerOccupant"
        lblOwnerOccupant.Size = New Size(586, 30)
        lblOwnerOccupant.TabIndex = 19
        lblOwnerOccupant.Text = "Name of Owner / Occupant"
        ' 
        ' txtOwnerOccupant
        ' 
        txtOwnerOccupant.BackColor = Color.FromArgb(CByte(250), CByte(250), CByte(250))
        txtOwnerOccupant.BorderStyle = BorderStyle.FixedSingle
        txtOwnerOccupant.Font = New Font("Segoe UI", 10F)
        txtOwnerOccupant.Location = New Point(35, 565)
        txtOwnerOccupant.Margin = New Padding(4)
        txtOwnerOccupant.Name = "txtOwnerOccupant"
        txtOwnerOccupant.PlaceholderText = "Full name of owner or occupant"
        txtOwnerOccupant.Size = New Size(583, 34)
        txtOwnerOccupant.TabIndex = 20
        ' 
        ' lblCallerInformation
        ' 
        lblCallerInformation.Anchor = AnchorStyles.Top Or AnchorStyles.Right
        lblCallerInformation.Font = New Font("Segoe UI", 9F, FontStyle.Bold)
        lblCallerInformation.ForeColor = Color.FromArgb(CByte(70), CByte(70), CByte(70))
        lblCallerInformation.Location = New Point(951, 533)
        lblCallerInformation.Margin = New Padding(4, 0, 4, 0)
        lblCallerInformation.Name = "lblCallerInformation"
        lblCallerInformation.Size = New Size(586, 30)
        lblCallerInformation.TabIndex = 21
        lblCallerInformation.Text = "Caller Information *"
        ' 
        ' txtCallerInformation
        ' 
        txtCallerInformation.Anchor = AnchorStyles.Top Or AnchorStyles.Right
        txtCallerInformation.BackColor = Color.FromArgb(CByte(250), CByte(250), CByte(250))
        txtCallerInformation.BorderStyle = BorderStyle.FixedSingle
        txtCallerInformation.Font = New Font("Segoe UI", 10F)
        txtCallerInformation.Location = New Point(951, 565)
        txtCallerInformation.Margin = New Padding(4)
        txtCallerInformation.Name = "txtCallerInformation"
        txtCallerInformation.PlaceholderText = "Name / contact of person who called it in"
        txtCallerInformation.Size = New Size(583, 34)
        txtCallerInformation.TabIndex = 22
        ' 
        ' lblCasualties
        ' 
        lblCasualties.Font = New Font("Segoe UI", 9F, FontStyle.Bold)
        lblCasualties.ForeColor = Color.FromArgb(CByte(70), CByte(70), CByte(70))
        lblCasualties.Location = New Point(35, 606)
        lblCasualties.Margin = New Padding(4, 0, 4, 0)
        lblCasualties.Name = "lblCasualties"
        lblCasualties.Size = New Size(198, 30)
        lblCasualties.TabIndex = 23
        lblCasualties.Text = "No. of Casualties"
        ' 
        ' txtCasualties
        ' 
        txtCasualties.BackColor = Color.FromArgb(CByte(250), CByte(250), CByte(250))
        txtCasualties.BorderStyle = BorderStyle.FixedSingle
        txtCasualties.Font = New Font("Segoe UI", 10F)
        txtCasualties.Location = New Point(35, 638)
        txtCasualties.Margin = New Padding(4)
        txtCasualties.Name = "txtCasualties"
        txtCasualties.PlaceholderText = "0"
        txtCasualties.Size = New Size(196, 34)
        txtCasualties.TabIndex = 24
        ' 
        ' lblDamageEstimate
        ' 
        lblDamageEstimate.Anchor = AnchorStyles.Top Or AnchorStyles.Right
        lblDamageEstimate.Font = New Font("Segoe UI", 9F, FontStyle.Bold)
        lblDamageEstimate.ForeColor = Color.FromArgb(CByte(70), CByte(70), CByte(70))
        lblDamageEstimate.Location = New Point(951, 606)
        lblDamageEstimate.Margin = New Padding(4, 0, 4, 0)
        lblDamageEstimate.Name = "lblDamageEstimate"
        lblDamageEstimate.Size = New Size(586, 30)
        lblDamageEstimate.TabIndex = 25
        lblDamageEstimate.Text = "Estimated Damage (PHP)"
        ' 
        ' txtDamageEstimate
        ' 
        txtDamageEstimate.Anchor = AnchorStyles.Top Or AnchorStyles.Right
        txtDamageEstimate.BackColor = Color.FromArgb(CByte(250), CByte(250), CByte(250))
        txtDamageEstimate.BorderStyle = BorderStyle.FixedSingle
        txtDamageEstimate.Font = New Font("Segoe UI", 10F)
        txtDamageEstimate.Location = New Point(951, 638)
        txtDamageEstimate.Margin = New Padding(4)
        txtDamageEstimate.Name = "txtDamageEstimate"
        txtDamageEstimate.PlaceholderText = "e.g. 500000"
        txtDamageEstimate.Size = New Size(583, 34)
        txtDamageEstimate.TabIndex = 26
        ' 
        ' lblCauseOfFire
        ' 
        lblCauseOfFire.Font = New Font("Segoe UI", 9F, FontStyle.Bold)
        lblCauseOfFire.ForeColor = Color.FromArgb(CByte(70), CByte(70), CByte(70))
        lblCauseOfFire.Location = New Point(242, 606)
        lblCauseOfFire.Margin = New Padding(4, 0, 4, 0)
        lblCauseOfFire.Name = "lblCauseOfFire"
        lblCauseOfFire.Size = New Size(378, 30)
        lblCauseOfFire.TabIndex = 27
        lblCauseOfFire.Text = "Cause of Fire"
        ' 
        ' txtCauseOfFire
        ' 
        txtCauseOfFire.BackColor = Color.FromArgb(CByte(250), CByte(250), CByte(250))
        txtCauseOfFire.BorderStyle = BorderStyle.FixedSingle
        txtCauseOfFire.Font = New Font("Segoe UI", 10F)
        txtCauseOfFire.Location = New Point(242, 640)
        txtCauseOfFire.Margin = New Padding(4)
        txtCauseOfFire.Name = "txtCauseOfFire"
        txtCauseOfFire.PlaceholderText = "e.g. Electrical short circuit"
        txtCauseOfFire.Size = New Size(376, 34)
        txtCauseOfFire.TabIndex = 28
        ' 
        ' lblRemarks
        ' 
        lblRemarks.Font = New Font("Segoe UI", 9F, FontStyle.Bold)
        lblRemarks.ForeColor = Color.FromArgb(CByte(70), CByte(70), CByte(70))
        lblRemarks.Location = New Point(35, 694)
        lblRemarks.Margin = New Padding(4, 0, 4, 0)
        lblRemarks.Name = "lblRemarks"
        lblRemarks.Size = New Size(1500, 30)
        lblRemarks.TabIndex = 29
        lblRemarks.Text = "Remarks / Notes"
        ' 
        ' txtRemarks
        ' 
        txtRemarks.BackColor = Color.FromArgb(CByte(250), CByte(250), CByte(250))
        txtRemarks.BorderStyle = BorderStyle.FixedSingle
        txtRemarks.Font = New Font("Segoe UI", 10F)
        txtRemarks.Location = New Point(35, 728)
        txtRemarks.Margin = New Padding(4)
        txtRemarks.Multiline = True
        txtRemarks.Name = "txtRemarks"
        txtRemarks.ScrollBars = ScrollBars.Vertical
        txtRemarks.Size = New Size(1498, 92)
        txtRemarks.TabIndex = 30
        ' 
        ' lblDocument
        ' 
        lblDocument.Font = New Font("Segoe UI", 9F, FontStyle.Bold)
        lblDocument.ForeColor = Color.FromArgb(CByte(70), CByte(70), CByte(70))
        lblDocument.Location = New Point(35, 825)
        lblDocument.Margin = New Padding(4, 0, 4, 0)
        lblDocument.Name = "lblDocument"
        lblDocument.Size = New Size(586, 30)
        lblDocument.TabIndex = 31
        lblDocument.Text = "Attached Document *"
        ' 
        ' txtDocumentName
        ' 
        txtDocumentName.Anchor = AnchorStyles.Top Or AnchorStyles.Left Or AnchorStyles.Right
        txtDocumentName.BackColor = Color.FromArgb(CByte(245), CByte(245), CByte(245))
        txtDocumentName.BorderStyle = BorderStyle.FixedSingle
        txtDocumentName.Font = New Font("Segoe UI", 10F)
        txtDocumentName.Location = New Point(35, 857)
        txtDocumentName.Margin = New Padding(4)
        txtDocumentName.Name = "txtDocumentName"
        txtDocumentName.PlaceholderText = "No document selected."
        txtDocumentName.ReadOnly = True
        txtDocumentName.Size = New Size(1288, 34)
        txtDocumentName.TabIndex = 32
        ' 
        ' btnBrowseDocument
        ' 
        btnBrowseDocument.Anchor = AnchorStyles.Top Or AnchorStyles.Right
        btnBrowseDocument.BackColor = Color.FromArgb(CByte(30), CByte(100), CByte(180))
        btnBrowseDocument.Cursor = Cursors.Hand
        btnBrowseDocument.FlatAppearance.BorderSize = 0
        btnBrowseDocument.FlatStyle = FlatStyle.Flat
        btnBrowseDocument.Font = New Font("Segoe UI", 9.5F, FontStyle.Bold)
        btnBrowseDocument.ForeColor = Color.White
        btnBrowseDocument.Location = New Point(1333, 857)
        btnBrowseDocument.Margin = New Padding(4)
        btnBrowseDocument.Name = "btnBrowseDocument"
        btnBrowseDocument.Size = New Size(202, 37)
        btnBrowseDocument.TabIndex = 33
        btnBrowseDocument.Text = "Browse..."
        btnBrowseDocument.UseVisualStyleBackColor = False
        ' 
        ' btnSave
        ' 
        btnSave.BackColor = Color.FromArgb(CByte(180), CByte(20), CByte(20))
        btnSave.Cursor = Cursors.Hand
        btnSave.FlatAppearance.BorderSize = 0
        btnSave.FlatStyle = FlatStyle.Flat
        btnSave.Font = New Font("Segoe UI", 10F, FontStyle.Bold)
        btnSave.ForeColor = Color.White
        btnSave.Location = New Point(35, 902)
        btnSave.Margin = New Padding(4)
        btnSave.Name = "btnSave"
        btnSave.Size = New Size(270, 46)
        btnSave.TabIndex = 34
        btnSave.Text = "SAVE RECORD"
        btnSave.UseVisualStyleBackColor = False
        ' 
        ' btnClear
        ' 
        btnClear.BackColor = Color.White
        btnClear.Cursor = Cursors.Hand
        btnClear.FlatAppearance.BorderColor = Color.FromArgb(CByte(200), CByte(200), CByte(200))
        btnClear.FlatStyle = FlatStyle.Flat
        btnClear.Font = New Font("Segoe UI", 10F)
        btnClear.ForeColor = Color.FromArgb(CByte(100), CByte(100), CByte(100))
        btnClear.Location = New Point(323, 902)
        btnClear.Margin = New Padding(4)
        btnClear.Name = "btnClear"
        btnClear.Size = New Size(240, 46)
        btnClear.TabIndex = 35
        btnClear.Text = "CLEAR FORM"
        btnClear.UseVisualStyleBackColor = False
        ' 
        ' UcAddRecord
        ' 
        AutoScaleDimensions = New SizeF(144F, 144F)
        AutoScaleMode = AutoScaleMode.Dpi
        AutoScroll = True
        BackColor = Color.FromArgb(CByte(240), CByte(242), CByte(245))
        Controls.Add(pnlFormCard)
        Font = New Font("Segoe UI", 9F)
        Margin = New Padding(4)
        Name = "UcAddRecord"
        Size = New Size(1679, 1016)
        pnlFormCard.ResumeLayout(False)
        pnlFormCard.PerformLayout()
        ResumeLayout(False)

    End Sub

    Friend WithEvents pnlFormCard           As Panel
    Friend WithEvents pnlDivider            As Panel
    Friend WithEvents lblPageTitle          As Label
    Friend WithEvents lblPageSub            As Label
    Friend WithEvents lblIncidentNo         As Label
    Friend WithEvents txtIncidentNo         As TextBox
    Friend WithEvents lblIncidentType       As Label
    Friend WithEvents cboIncidentType       As ComboBox
    Friend WithEvents lblIncidentDateTime   As Label
    Friend WithEvents dtpIncidentDateTime   As DateTimePicker
    Friend WithEvents lblAlarmLevel         As Label
    Friend WithEvents cboAlarmLevel         As ComboBox
    Friend WithEvents lblResponseTime       As Label
    Friend WithEvents dtpResponseTime       As DateTimePicker
    Friend WithEvents lblStatus             As Label
    Friend WithEvents cboStatus             As ComboBox
    Friend WithEvents lblInvolvedProperty   As Label
    Friend WithEvents txtInvolvedProperty   As TextBox
    Friend WithEvents lblAddress            As Label
    Friend WithEvents txtAddress            As TextBox
    Friend WithEvents lblOwnerOccupant      As Label
    Friend WithEvents txtOwnerOccupant      As TextBox
    Friend WithEvents lblCallerInformation  As Label
    Friend WithEvents txtCallerInformation  As TextBox
    Friend WithEvents lblCasualties         As Label
    Friend WithEvents txtCasualties         As TextBox
    Friend WithEvents lblDamageEstimate     As Label
    Friend WithEvents txtDamageEstimate     As TextBox
    Friend WithEvents lblCauseOfFire        As Label
    Friend WithEvents txtCauseOfFire        As TextBox
    Friend WithEvents lblRemarks            As Label
    Friend WithEvents txtRemarks            As TextBox
    Friend WithEvents lblDocument           As Label
    Friend WithEvents txtDocumentName       As TextBox
    Friend WithEvents btnBrowseDocument     As Button
    Friend WithEvents btnSave               As Button
    Friend WithEvents btnClear              As Button

End Class
